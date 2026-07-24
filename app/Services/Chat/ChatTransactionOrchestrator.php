<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Actions\ProcessTransactionAction;
use App\DTO\AiProviderRequest;
use App\DTO\AIParseResultMulti;
use App\DTO\ConfidenceScoreContext;
use App\DTO\MultiTransactionItem;
use App\DTO\MultiTransactionResult;
use App\DTO\ParsedTransaction;
use App\DTO\ResolvedTransaction;
use App\Enums\MultiTransactionErrorCode;
use App\Enums\TransactionIntent;
use App\Enums\TransactionSource;
use App\Enums\WalletSide;
use App\Events\AiTransactionLinked;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\CategoryNotFoundException;
use App\Exceptions\WalletNotFoundException;
use App\Models\Category;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\AiParseLogService;
use App\Services\AI\Context\AIContext;
use App\Services\AI\Context\AIContextBuilder;
use App\Services\AI\Context\ContextSnapshot;
use App\Services\AI\Memory\UserMemoryService;
use App\Services\AI\Prompt\PromptRenderer;
use App\Services\AI\Scoring\ConfidenceScoringEngine;
use App\Services\AI\TransactionResolver;
use App\Services\Wallet\WalletResolutionService;
use App\Support\StringUtils;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ChatTransactionOrchestrator
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly TransactionResolver $resolver,
        private readonly ProcessTransactionAction $transactionAction,
        private readonly UserMemoryService $memoryService,
        private readonly ConfidenceScoringEngine $scoringEngine,
        private readonly AiParseLogService $parseLogService,
        private readonly MultiTransactionRouter $multiRouter,
        private readonly MultiTransactionProcessor $multiProcessor,
        private readonly WalletResolutionService $walletResolution,
        private readonly DraftPayloadBuilder $draftBuilder,
    ) {}

    /**
     * Entry point utama dari Telegram webhook.
     *
     * @return array{success: bool, message: string, transaction?: TransactionLog, transactions?: TransactionLog[]}
     */
    public function process(User $user, string $text, string $source = 'TEL'): array
    {
        try {
            // Kirim hanya wallet non-System ke AI agar AI tidak salah pilih/menyebutkan
            // wallet sistem (External, Merchant, Hutang, Piutang) sebagai target transaksi.
            // Wallet sistem dikelola internal oleh resolver — AI tidak perlu tahu.
            $wallets = $user->wallets()
                ->where('group_type', '!=', 'System')
                ->get(['id', 'name', 'group_type', 'keyword', 'balance'])
                ->toArray();
            $categories = $user->categories()->get(['id', 'category_name', 'type_id', 'keyword'])->toArray();
            $activeMemories = $this->memoryService->getTopRelevantMemories($user->id, $text);

            // ── Build prompt via ContextSnapshot → AIContext → PromptRenderer ──
            $snapshot = new ContextSnapshot(
                user: $user,
                userInput: $text,
                wallets: collect($wallets),
                categories: collect($categories),
                activeMemories: $activeMemories,
            );
            $aiContext = (new AIContextBuilder)->build($snapshot);
            $prompt = $this->multiRouter->isMultiTransaction($text)
                ? (new PromptRenderer)->renderMulti($aiContext)
                : (new PromptRenderer)->renderSingle($aiContext);

            // ── ROUTING: single vs multi ──────────────────────────────
            if ($this->multiRouter->isMultiTransaction($text)) {
                return $this->processMulti($user, $text, $wallets, $categories, $activeMemories, $source, $prompt);
            }

            return $this->processSingle($user, $text, $wallets, $categories, $activeMemories, $source, $prompt);

        } catch (CategoryNotFoundException|WalletNotFoundException $e) {
            $errorCode = $e instanceof CategoryNotFoundException ? 'CATEGORY_NOT_FOUND' : 'WALLET_NOT_FOUND';

            return ['success' => false, 'error_code' => $errorCode, 'message' => implode("\n", [
                '🔍 *Data Tidak Ditemukan — Semua Transaksi Dibatalkan*',
                '',
                $e->getMessage(),
                '',
                'Pastikan nama dompet (contoh: *cash*, *dana*, *spay*) dan kategori sudah terdaftar di Web.',
                'Semua transaksi dalam pesan ini dibatalkan.',
            ])];
        } catch (AiConfigurationException $e) {
            Log::warning('AI tidak dikonfigurasi', ['user_id' => $user->id, 'message' => $e->getMessage()]);

            return ['success' => false, 'error_code' => 'AI_NOT_CONFIGURED', 'message' => implode("\n", [
                '⚙️ *AI Belum Dikonfigurasi*',
                '',
                'Python sedang offline dan belum ada AI cadangan yang aktif.',
                '',
                '👉 Buka *Dashboard Web → Settings → AI* lalu centang *"Aktifkan sebagai AI Cadangan"* pada provider yang sudah kamu isi API key-nya.',
            ])];
        } catch (AiRateLimitException $e) {
            $provider = $e->getMessage();

            return ['success' => false, 'error_code' => 'AI_RATE_LIMIT', 'message' => implode("\n", [
                "⚠️ *Kuota API {$provider} Habis*",
                '',
                'Limit token/request harian kamu sudah tercapai.',
                '• Tunggu reset kuota (biasanya tengah malam)',
                "• Atau topup/upgrade plan API kamu di dashboard {$provider}",
            ])];
        } catch (AiTimeoutException $e) {
            $provider = $e->getMessage();

            return ['success' => false, 'error_code' => 'AI_TIMEOUT', 'message' => "⏳ *Server {$provider} Sedang Sibuk*\n\nCoba kirim ulang pesanmu dalam 1-2 menit ya Bos."];
        } catch (AiProviderException $e) {
            Log::error('AI provider error', ['user_id' => $user->id, 'message' => $e->getMessage()]);

            return ['success' => false, 'error_code' => 'AI_PROVIDER_ERROR', 'message' => "❌ *Terjadi Error pada AI*\n\n`".$e->getMessage()."`\n\nCoba lagi nanti."];
        } catch (InvalidArgumentException|RuntimeException $e) {
            return ['success' => false, 'error_code' => 'VALIDATION_ERROR', 'message' => "⚠️ *Gagal diproses:*\n".$e->getMessage()];
        } catch (ModelNotFoundException $e) {
            return ['success' => false, 'error_code' => 'DATA_NOT_FOUND', 'message' => implode("\n", [
                '🔍 *Data Tidak Ditemukan*',
                '',
                'Dompet atau kategori yang dimaksud tidak ada di sistem.',
                'Pastikan nama dompet (contoh: *bca*, *dana*, *cash*) sudah kamu daftarkan di Web.',
            ])];
        } catch (Throwable $e) {
            Log::error('Chat Orchestrator Error', [
                'user_id' => $user->id, 'text' => $text,
                'exception' => $e, 'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'error_code' => 'SYSTEM_ERROR', 'message' => '❌ Waduh, ada error sistem internal Bos. Coba lagi nanti ya.'];
        }
    }

    // ════════════════════════════════════════════════════════════════
    // SINGLE TRANSACTION (existing flow — tidak diubah)
    // ════════════════════════════════════════════════════════════════

    private function processSingle(
        User $user, string $text,
        array $wallets, array $categories, array $activeMemories,
        string $source,
        string $prompt = '',
    ): array {
        $aiResult = $this->aiManager->parseTransaction($user, $text, $wallets, $categories, $activeMemories, $prompt);

        if (! $aiResult->success || ! $aiResult->transaction) {
            return ['success' => false, 'error_code' => 'AI_PARSE_FAILED', 'message' => '❌ AI Gagal memproses: '.($aiResult->error ?? 'Format tidak dikenali.')];
        }

        $parsed = $aiResult->transaction;
        $isWebSource = strtoupper($source) === 'WEB';

        $guardError = $this->draftBuilder->validateParsed($parsed);
        if ($guardError !== null) {
            $message = $guardError === 'INVALID_AMOUNT'
                ? "🤔 *Nominalnya berapa Bos?*\nAku bingung nih, kamu belum nyebutin jumlah uangnya."
                : "🧐 *Masuk kategori apa nih?*\nSebutkan nama barang atau kegiatannya ya.";
            return ['success' => false, 'error_code' => $guardError, 'message' => $message];
        }

        $isDebtRelated = in_array($parsed->transactionType, [TransactionIntent::Debt, TransactionIntent::Receivable]);
        preg_match('/#([a-zA-Z0-9_]+)/', $text, $matches);
        $extractedSubject = $matches[1] ?? null;

        if ($isDebtRelated && ! $extractedSubject) {
            return ['success' => false, 'error_code' => 'MISSING_SUBJECT', 'message' => "🤝 *Nama orangnya siapa Bos?*\nKarena ini transaksi Hutang/Piutang, kamu WAJIB pakai hashtag.\n\n💡 *Contoh:* pinjam uang 50k dana #Budi"];
        }

        $finalSubject = $extractedSubject ?? $user->name;
        $threshold = (float) config('bendaharaku.ai.confidence.threshold_auto_clear', 0.85);
        $finalConfidence = 0.0;
        $resolved = null;
        $walletExplicitlyMentioned = $this->hasExplicitWalletMention($text, $user);

        try {
            $resolved = $this->resolver->resolve($user, $parsed);

            $scoreContext = new ConfidenceScoreContext(
                user: $user, inputText: $text, parseResult: $aiResult,
                resolvedTransaction: $resolved, activeMemories: $activeMemories,
                wallets: $wallets, categories: $categories,
            );
            $finalConfidence = $this->scoringEngine->calculateFinalScore($scoreContext);

            $resolved = ($isWebSource && ! $walletExplicitlyMentioned && $parsed->transactionType !== TransactionIntent::Transfer)
                ? $this->resolveWebDraftWithoutWallet($user, $parsed, $finalSubject)
                : new ResolvedTransaction(
                    amount: $resolved->amount,
                    categoryId: $resolved->categoryId,
                    sourceWalletId: $resolved->sourceWalletId,
                    destinationWalletId: $resolved->destinationWalletId,
                    subject: $resolved->subject,
                    notes: $resolved->notes,
                    isCleared: ($finalConfidence >= $threshold && (! $isWebSource || $walletExplicitlyMentioned)),
                );

        } catch (CategoryNotFoundException|WalletNotFoundException $e) {
            if ($isWebSource && ! $walletExplicitlyMentioned && $parsed->category && $parsed->transactionType !== TransactionIntent::Transfer) {
                $resolved = $this->resolveWebDraftWithoutWallet($user, $parsed, $finalSubject);
                $finalConfidence = 0.0;
            } else {
                $resolved = new ResolvedTransaction(
                    amount: $parsed->amount, categoryId: null,
                    sourceWalletId: null, destinationWalletId: null,
                    subject: $finalSubject,
                    notes: $text."\n[ERROR AI: ".$e->getMessage().']',
                    isCleared: false
                );
                $finalConfidence = 0.0;
            }
        }

        if ($resolved === null) {
            return ['success' => false, 'error_code' => 'SETUP_FAILED', 'message' => '❌ Gagal menyiapkan data transaksi.'];
        }

        if ($resolved->isCleared === false && ($resolved->categoryId === null || $resolved->sourceWalletId === null)) {
            $this->parseLogService->createLog(user: $user, inputText: $text, result: $aiResult, finalConfidence: $finalConfidence);

            return ['success' => false, 'error_code' => 'DRAFT_SAVED', 'message' => implode("\n", [
                '📝 *MASUK DRAFT (Butuh Cek Web)*', '',
                "AI tidak dapat mengenali kategori atau dompet dari: _{$text}_", '',
                'Coba sebutkan nama dompet dan kategori yang sudah terdaftar.',
                'Atau cek & lengkapi transaksi draft-nya di 👉 *Dashboard Web*.',
            ])];
        }

        // ── WEB Source atau Draft (is_cleared = false): Simpan ke transaction_drafts ──
        if ($isWebSource || ! $resolved->isCleared) {
            return $this->processSingleWebDraft(
                user: $user,
                text: $text,
                parsed: $parsed,
                resolved: $resolved,
                aiResult: $aiResult,
                finalConfidence: $finalConfidence,
                finalSubject: $finalSubject,
                wallets: $wallets,
                categories: $categories,
            );
        }

        // ── Non-WEB (Telegram, dll): Simpan langsung ke transaction_logs ──
        $parseLogId = $this->parseLogService->createLog(user: $user, inputText: $text, result: $aiResult, finalConfidence: $finalConfidence);

        $transactionLog = $this->transactionAction->create(
            data: [
                'date' => now()->format('Y-m-d'),
                'category_id' => $resolved->categoryId,
                'source_wallet_id' => $resolved->sourceWalletId,
                'destination_wallet_id' => $resolved->destinationWalletId,
                'amount' => $resolved->amount,
                'subject' => $finalSubject,
                'notes' => $text.($resolved->isCleared ? '' : ' [DRAFT AI]'),
                'is_cleared' => $resolved->isCleared,
            ],
            userId: $user->id,
            sourcePrefix: $source,
            source: TransactionSource::TELEGRAM,
        );

        if ($parseLogId > 0) {
            event(new AiTransactionLinked($transactionLog, $parseLogId));
        }

        return [
            'success' => true,
            'message' => 'Transaksi berhasil dicatat.',
            'provider' => $aiResult->provider,
            'model' => $aiResult->model,
            'confidence' => $finalConfidence,
            'usage' => $aiResult->usage,
            'transaction' => $transactionLog->load(['category', 'sourceWallet', 'destinationWallet', 'type']),
        ];
    }

    /**
     * Simpan hasil parsing AI ke transaction_drafts untuk source WEB atau draft.
     * Tidak membuat TransactionLog — hanya menyimpan preview draft.
     *
     * @return array{success: bool, is_web_draft: bool, draft: TransactionDraft, ...}
     */
    private function processSingleWebDraft(
        User $user,
        string $text,
        ParsedTransaction $parsed,
        ResolvedTransaction $resolved,
        object $aiResult,
        float $finalConfidence,
        string $finalSubject,
        array $wallets,
        array $categories,
    ): array {
        // ── Resolusi nama kategori ───────────────────────────────────
        $categoryName = null;
        if ($resolved->categoryId) {
            // Cari di koleksi categories yang sudah di-load
            foreach ($categories as $cat) {
                if (($cat['id'] ?? null) === $resolved->categoryId) {
                    $categoryName = $cat['category_name'] ?? null;
                    break;
                }
            }
            // Fallback ke DB jika tidak ditemukan di koleksi
            if ($categoryName === null) {
                $categoryName = Category::find($resolved->categoryId)?->category_name;
            }
        }
        // Fallback: gunakan nama kategori dari hasil parsing AI
        if ($categoryName === null) {
            $categoryName = $parsed->category;
        }

        // ── Resolusi nama wallet ─────────────────────────────────────
        // Load semua wallet user (termasuk System) untuk resolusi nama
        $allWallets = $user->wallets()->get(['id', 'name', 'group_type']);

        $sourceWalletName = null;
        $destinationWalletName = null;

        foreach ($allWallets as $wallet) {
            if ($wallet->id === $resolved->sourceWalletId) {
                $sourceWalletName = $wallet->name;
            }
            if ($wallet->id === $resolved->destinationWalletId) {
                $destinationWalletName = $wallet->name;
            }
        }

        // ── Tentukan needs_wallet ────────────────────────────────────
        $missingWalletSide = $resolved->missingWalletSide;
        $needsWallet = $missingWalletSide !== null
            ? $missingWalletSide !== WalletSide::None->value
            : ($sourceWalletName !== null && $this->walletResolution->isExternalByName($sourceWalletName))
                || ($destinationWalletName !== null && $this->walletResolution->isExternalByName($destinationWalletName));

        // ── Resolve type_key dari transactionType ────────────────────
        $typeKey = $parsed->transactionType?->toTypeKey() ?? 'expense';

        // ── Resolve active conversation ID ───────────────────────────
        $activeConversationId = $user->conversations()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->latest()
            ->value('id');

        // ── Buat TransactionDraft dengan shared payload builder ──────
        $draft = TransactionDraft::create([
            'user_id' => $user->id,
            'conversation_id' => $activeConversationId,
            'ai_provider' => $aiResult->provider,
            'ai_model' => $aiResult->model,
            'draft_type' => 'single',
            'missing_wallet_side' => $missingWalletSide,
            'status' => 'pending',
            'ai_confidence' => $finalConfidence,
            'original_text' => $text,
            'expires_at' => now()->addHours(24),
            'payload' => $this->draftBuilder->build(
                resolved: $resolved,
                categoryName: $categoryName,
                sourceWalletName: $sourceWalletName,
                destinationWalletName: $destinationWalletName,
                subject: $finalSubject,
                notes: $text,
                typeKey: $typeKey,
                needsWallet: $needsWallet,
            ),
        ]);

        // Catat ke parse log untuk AI analytics (tidak ada transaction_log ID karena belum dibuat)
        $this->parseLogService->createLog(
            user: $user,
            inputText: $text,
            result: $aiResult,
            finalConfidence: $finalConfidence,
        );

        return [
            'success' => true,
            'is_web_draft' => true,
            'draft' => $draft,
            'provider' => $aiResult->provider,
            'model' => $aiResult->model,
            'confidence' => $finalConfidence,
            'usage' => $aiResult->usage,
        ];
    }

    private function hasExplicitWalletMention(string $text, User $user): bool
    {
        return $this->walletResolution->userWalletMentionedInText($text, $user);
    }

    private function resolveWebDraftWithoutWallet(User $user, ParsedTransaction $parsed, string $subject): ResolvedTransaction
    {
        $categories = $user->categories()->get();
        $category = StringUtils::findByNameOrKeyword($categories, $parsed->category);
        if ($category === null) {
            throw new CategoryNotFoundException("Kategori '{$parsed->category}' tidak terdaftar.");
        }

        [$sourceWalletId, $destinationWalletId, $missingWalletSide] = $this->walletResolution->resolveDraftWalletAllocation(
            transactionType: $parsed->transactionType ?? TransactionIntent::Expense,
            userId: $user->id,
            categoryName: $category->category_name,
        );

        return new ResolvedTransaction(
            amount: $parsed->amount,
            categoryId: $category->id,
            sourceWalletId: $sourceWalletId,
            destinationWalletId: $destinationWalletId,
            subject: $parsed->subject ?? $subject,
            notes: ($parsed->notes ?: '').' [DRAFT AI: wallet belum dipilih]',
            isCleared: false,
            missingWalletSide: $missingWalletSide,
        );
    }

    private function processMulti(
        User $user, string $text,
        array $wallets, array $categories, array $activeMemories,
        string $source,
        string $prompt = '',
    ): array {
        $result = $this->multiProcessor->process($user, $text, $wallets, $categories, $activeMemories, $source, $prompt);

        if (isset($result['__fallback_to_single']) && $result['__fallback_to_single']) {
            return $this->processSingle($user, $text, $wallets, $categories, $activeMemories, $source);
        }

        return $result;
    }
}
