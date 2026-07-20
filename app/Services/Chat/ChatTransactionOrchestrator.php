<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\User;
use App\Models\TransactionLog;
use App\Services\AI\AIManager;
use App\Services\AI\TransactionResolver;
use App\Actions\ProcessTransactionAction;
use App\Services\AI\Memory\UserMemoryService;
use App\Services\AI\Scoring\ConfidenceScoringEngine;
use App\Services\AI\AiParseLogService;
use App\Services\AI\AiProviderFactory;
use App\Services\AI\AiPreferenceManager;
use App\Services\AI\AiCredentialManager;
use App\Services\AI\Prompt\MultiTransactionPromptBuilder;
use App\DTO\AiProviderRequest;
use App\DTO\ConfidenceScoreContext;
use App\DTO\ParsedTransaction;
use App\DTO\ResolvedTransaction;
use App\DTO\MultiTransactionResult;
use App\DTO\MultiTransactionItem;
use App\Enums\TransactionIntent;
use App\Enums\MultiTransactionErrorCode;
use App\Events\TransactionPosted;
use App\Exceptions\CategoryNotFoundException;
use App\Exceptions\WalletNotFoundException;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiProviderException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Throwable;
use InvalidArgumentException;
use RuntimeException;

class ChatTransactionOrchestrator
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly TransactionResolver $resolver,
        private readonly ProcessTransactionAction $transactionAction,
        private readonly UserMemoryService $memoryService,
        private readonly ConfidenceScoringEngine $scoringEngine,
        private readonly AiParseLogService $parseLogService,
        // Komponen baru untuk multi-transaction
        private readonly MultiTransactionRouter $multiRouter,
        private readonly AiPreferenceManager $preferenceManager,
        private readonly AiCredentialManager $credentialManager,
        private readonly AiProviderFactory $providerFactory,
        private readonly MultiTransactionPromptBuilder $multiPromptBuilder,
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
            $wallets    = $user->wallets()
                ->where('group_type', '!=', 'System')
                ->get(['id', 'name', 'group_type', 'keyword'])
                ->toArray();
            $categories = $user->categories()->get(['id', 'category_name', 'type_id', 'keyword'])->toArray();
            $activeMemories = $this->memoryService->getTopRelevantMemories($user->id, $text);

            // ── ROUTING: single vs multi ──────────────────────────────
            if ($this->multiRouter->isMultiTransaction($text)) {
                return $this->processMulti($user, $text, $wallets, $categories, $activeMemories, $source);
            }

            return $this->processSingle($user, $text, $wallets, $categories, $activeMemories, $source);

        } catch (CategoryNotFoundException | WalletNotFoundException $e) {
            return ['success' => false, 'message' => implode("\n", [
                "🔍 *Data Tidak Ditemukan — Semua Transaksi Dibatalkan*",
                "",
                $e->getMessage(),
                "",
                "Pastikan nama dompet (contoh: *cash*, *dana*, *spay*) dan kategori sudah terdaftar di Web.",
                "Semua transaksi dalam pesan ini dibatalkan.",
            ])];
        } catch (AiConfigurationException $e) {
            Log::warning('AI tidak dikonfigurasi', ['user_id' => $user->id, 'message' => $e->getMessage()]);
            return ['success' => false, 'message' => implode("\n", [
                "⚙️ *AI Belum Dikonfigurasi*",
                "",
                "Python sedang offline dan belum ada AI cadangan yang aktif.",
                "",
                "👉 Buka *Dashboard Web → Settings → AI* lalu centang *\"Aktifkan sebagai AI Cadangan\"* pada provider yang sudah kamu isi API key-nya.",
            ])];
        } catch (AiRateLimitException $e) {
            $provider = $e->getMessage();
            return ['success' => false, 'message' => implode("\n", [
                "⚠️ *Kuota API {$provider} Habis*",
                "",
                "Limit token/request harian kamu sudah tercapai.",
                "• Tunggu reset kuota (biasanya tengah malam)",
                "• Atau topup/upgrade plan API kamu di dashboard {$provider}",
            ])];
        } catch (AiTimeoutException $e) {
            $provider = $e->getMessage();
            return ['success' => false, 'message' => "⏳ *Server {$provider} Sedang Sibuk*\n\nCoba kirim ulang pesanmu dalam 1-2 menit ya Bos."];
        } catch (AiProviderException $e) {
            Log::error('AI provider error', ['user_id' => $user->id, 'message' => $e->getMessage()]);
            return ['success' => false, 'message' => "❌ *Terjadi Error pada AI*\n\n`" . $e->getMessage() . "`\n\nCoba lagi nanti."];
        } catch (InvalidArgumentException | \RuntimeException $e) {
            return ['success' => false, 'message' => "⚠️ *Gagal diproses:*\n" . $e->getMessage()];
        } catch (ModelNotFoundException $e) {
            return ['success' => false, 'message' => implode("\n", [
                "🔍 *Data Tidak Ditemukan*",
                "",
                "Dompet atau kategori yang dimaksud tidak ada di sistem.",
                "Pastikan nama dompet (contoh: *bca*, *dana*, *cash*) sudah kamu daftarkan di Web.",
            ])];
        } catch (Throwable $e) {
            Log::error('Chat Orchestrator Error', [
                'user_id' => $user->id, 'text' => $text,
                'exception' => $e, 'message' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => "❌ Waduh, ada error sistem internal Bos. Coba lagi nanti ya."];
        }
    }

    // ════════════════════════════════════════════════════════════════
    // SINGLE TRANSACTION (existing flow — tidak diubah)
    // ════════════════════════════════════════════════════════════════

    private function processSingle(
        User $user, string $text,
        array $wallets, array $categories, array $activeMemories,
        string $source
    ): array {
        $aiResult = $this->aiManager->parseTransaction($user, $text, $wallets, $categories, $activeMemories);

        if (!$aiResult->success || !$aiResult->transaction) {
            return ['success' => false, 'message' => "❌ AI Gagal memproses: " . ($aiResult->error ?? "Format tidak dikenali.")];
        }

        $parsed = $aiResult->transaction;
        $isWebSource = strtoupper($source) === 'WEB';

        if (!$parsed->amount) {
            return ['success' => false, 'message' => "🤔 *Nominalnya berapa Bos?*\nAku bingung nih, kamu belum nyebutin jumlah uangnya."];
        }
        if (!$parsed->category) {
            return ['success' => false, 'message' => "🧐 *Masuk kategori apa nih?*\nSebutkan nama barang atau kegiatannya ya."];
        }

        $isDebtRelated = in_array($parsed->transactionType, [TransactionIntent::Debt, TransactionIntent::Receivable]);
        preg_match('/#([a-zA-Z0-9_]+)/', $text, $matches);
        $extractedSubject = $matches[1] ?? null;

        if ($isDebtRelated && !$extractedSubject) {
            return ['success' => false, 'message' => "🤝 *Nama orangnya siapa Bos?*\nKarena ini transaksi Hutang/Piutang, kamu WAJIB pakai hashtag.\n\n💡 *Contoh:* pinjam uang 50k dana #Budi"];
        }

        $finalSubject    = $extractedSubject ?? $user->name;
        $threshold       = (float) config('bendaharaku.ai.confidence.threshold_auto_clear', 0.85);
        $finalConfidence = 0.0;
        $resolved        = null;
        $walletExplicitlyMentioned = $this->hasExplicitWalletMention($text, $wallets);

        try {
            $resolved = $this->resolver->resolve($user, $parsed);

            $scoreContext = new ConfidenceScoreContext(
                user: $user, inputText: $text, parseResult: $aiResult,
                resolvedTransaction: $resolved, activeMemories: $activeMemories
            );
            $finalConfidence = $this->scoringEngine->calculateFinalScore($scoreContext);

            $resolved = ($isWebSource && !$walletExplicitlyMentioned && $parsed->transactionType !== TransactionIntent::Transfer)
                ? $this->resolveWebDraftWithoutWallet($user, $parsed, $finalSubject)
                : new ResolvedTransaction(
                    amount:              $resolved->amount,
                    categoryId:          $resolved->categoryId,
                    sourceWalletId:      $resolved->sourceWalletId,
                    destinationWalletId: $resolved->destinationWalletId,
                    subject:             $resolved->subject,
                    notes:               $resolved->notes,
                    isCleared:           ($finalConfidence >= $threshold && (!$isWebSource || $walletExplicitlyMentioned)),
                );

        } catch (CategoryNotFoundException | WalletNotFoundException $e) {
            if ($isWebSource && !$walletExplicitlyMentioned && $parsed->category && $parsed->transactionType !== TransactionIntent::Transfer) {
                $resolved = $this->resolveWebDraftWithoutWallet($user, $parsed, $finalSubject);
                $finalConfidence = 0.0;
            } else {
                $resolved = new ResolvedTransaction(
                    amount: $parsed->amount, categoryId: null,
                    sourceWalletId: null, destinationWalletId: null,
                    subject: $finalSubject,
                    notes: $text . "\n[ERROR AI: " . $e->getMessage() . "]",
                    isCleared: false
                );
                $finalConfidence = 0.0;
            }
        }

        if ($resolved === null) {
            return ['success' => false, 'message' => '❌ Gagal menyiapkan data transaksi.'];
        }

        if ($resolved->isCleared === false && ($resolved->categoryId === null || $resolved->sourceWalletId === null)) {
            $this->parseLogService->createLog(user: $user, inputText: $text, result: $aiResult, finalConfidence: $finalConfidence);
            return ['success' => false, 'message' => implode("\n", [
                "📝 *MASUK DRAFT (Butuh Cek Web)*", "",
                "AI tidak dapat mengenali kategori atau dompet dari: _{$text}_", "",
                "Coba sebutkan nama dompet dan kategori yang sudah terdaftar.",
                "Atau cek & lengkapi transaksi draft-nya di 👉 *Dashboard Web*.",
            ])];
        }

        $parseLogId = $this->parseLogService->createLog(user: $user, inputText: $text, result: $aiResult, finalConfidence: $finalConfidence);

        $transactionLog = $this->transactionAction->create([
            'date'                  => now()->format('Y-m-d'),
            'category_id'           => $resolved->categoryId,
            'source_wallet_id'      => $resolved->sourceWalletId,
            'destination_wallet_id' => $resolved->destinationWalletId,
            'amount'                => $resolved->amount,
            'subject'               => $finalSubject,
            'notes'                 => $text . ($resolved->isCleared ? '' : ' [DRAFT AI]'),
            'is_cleared'            => $resolved->isCleared,
        ], $user->id, $source);

        event(new TransactionPosted($user, $transactionLog, $parseLogId));

        return [
            'success'     => true,
            'message'     => 'Transaksi berhasil dicatat.',
            'provider'    => $aiResult->provider,
            'model'       => $aiResult->model,
            'confidence'  => $finalConfidence,
            'usage'       => $aiResult->usage,
            'transaction' => $transactionLog->load(['category', 'sourceWallet', 'destinationWallet', 'type']),
        ];
    }

    private function hasExplicitWalletMention(string $text, array $wallets): bool
    {
        $normalizedText = mb_strtolower($text);

        foreach ($wallets as $wallet) {
            if (($wallet['group_type'] ?? null) === 'System') {
                continue;
            }

            $tokens = array_filter([
                $wallet['name'] ?? null,
                ...preg_split('/[,|;]+/', (string) ($wallet['keyword'] ?? ''), -1, PREG_SPLIT_NO_EMPTY),
            ]);

            foreach ($tokens as $token) {
                $token = trim(mb_strtolower((string) $token));
                if ($token !== '' && str_contains($normalizedText, $token)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function resolveWebDraftWithoutWallet(User $user, ParsedTransaction $parsed, string $subject): ResolvedTransaction
    {
        $wallets    = $user->wallets()->get();
        $categories = $user->categories()->get();
        $category   = $this->findCategoryForDraft($parsed->category, $categories);

        $externalWalletId   = $this->findSystemWalletId((string) config('bendaharaku.system_wallets.external'), $wallets);
        $merchantWalletId   = $this->findSystemWalletId((string) config('bendaharaku.system_wallets.merchant'), $wallets);
        $debtWalletId       = $this->findSystemWalletId((string) config('bendaharaku.system_wallets.debt'), $wallets);
        $receivableWalletId = $this->findSystemWalletId((string) config('bendaharaku.system_wallets.receivable'), $wallets);

        // Logika alokasi wallet untuk draft (wallet user belum diketahui):
        //
        // EXPENSE: uang keluar dari wallet user ke merchant
        //   source = placeholder (akan diganti user), dest = Merchant System
        //
        // INCOME: uang masuk dari luar ke wallet user
        //   source = External System, dest = placeholder (akan diganti user)
        //
        // DEBT (Hutang) — ada 2 sisi:
        //   "Dapat Hutangan" / terima hutang: source = System Hutang, dest = placeholder wallet user
        //   "Bayar Hutang": source = placeholder wallet user, dest = System Hutang
        //   → Karena draft, kita gunakan placeholder di sisi wallet user.
        //   Tapi karena kita hanya tahu intentnya Debt, kita default ke:
        //   source = System Hutang, dest = placeholder (user pilih wallet tujuan = penerima dana)
        //   — ini merepresentasikan "hutang masuk/terima hutang" yang paling umum.
        //
        // RECEIVABLE (Piutang) — ada 2 sisi:
        //   "Ngasih Piutang": source = placeholder wallet user, dest = System Piutang
        //   "Terima Bayar Piutang": source = System Piutang, dest = placeholder wallet user
        //   → AI sudah memilih kategori yang tepat. Kita lihat nama kategori untuk menentukan arah.
        //   Fallback: gunakan External System sebagai placeholder sisi wallet user.

        // Cek apakah ini "bayar/kembalikan" piutang (piutang masuk ke user) berdasarkan kategori
        $categoryName = mb_strtolower($category->category_name ?? '');
        $isReceivableReturn = $parsed->transactionType === TransactionIntent::Receivable
            && (str_contains($categoryName, 'terima') || str_contains($categoryName, 'bayar') || str_contains($categoryName, 'kembali'));
        $isDebtReturn = $parsed->transactionType === TransactionIntent::Debt
            && (str_contains($categoryName, 'bayar') || str_contains($categoryName, 'cicilan') || str_contains($categoryName, 'lunasi'));

        [$sourceWalletId, $destinationWalletId] = match (true) {
            // Receivable: piutang dikembalikan → dari System Piutang ke wallet user
            $isReceivableReturn                                      => [$receivableWalletId, $externalWalletId],
            // Receivable: memberi piutang → dari wallet user ke System Piutang
            $parsed->transactionType === TransactionIntent::Receivable => [$externalWalletId, $receivableWalletId],
            // Debt: bayar hutang → dari wallet user ke System Hutang
            $isDebtReturn                                            => [$externalWalletId, $debtWalletId],
            // Debt: terima hutang → dari System Hutang ke wallet user
            $parsed->transactionType === TransactionIntent::Debt    => [$debtWalletId, $externalWalletId],
            // Income: dari luar ke wallet user
            $parsed->transactionType === TransactionIntent::Income   => [$externalWalletId, $externalWalletId],
            // Expense (default): dari wallet user ke merchant
            default                                                  => [$externalWalletId, $merchantWalletId],
        };

        return new ResolvedTransaction(
            amount: $parsed->amount,
            categoryId: $category->id,
            sourceWalletId: $sourceWalletId,
            destinationWalletId: $destinationWalletId,
            subject: $parsed->subject ?? $subject,
            notes: ($parsed->notes ?: '') . ' [DRAFT AI: wallet belum dipilih]',
            isCleared: false,
        );
    }

    private function findCategoryForDraft(?string $text, \Illuminate\Database\Eloquent\Collection $categories): \App\Models\Category
    {
        if (blank($text)) {
            throw new CategoryNotFoundException('Input kategori kosong.');
        }

        $search = mb_strtolower(trim($text));

        $match = $categories->first(fn($c) => mb_strtolower($c->category_name) === $search);
        if ($match) {
            return $match;
        }

        $match = $categories->first(function ($category) use ($search) {
            if (blank($category->keyword)) {
                return false;
            }

            $tokens = preg_split('/[,|;]+/', mb_strtolower($category->keyword), -1, PREG_SPLIT_NO_EMPTY);
            return in_array($search, array_map('trim', $tokens), true);
        });

        if ($match) {
            return $match;
        }

        throw new CategoryNotFoundException("Kategori '{$text}' tidak terdaftar.");
    }

    private function findSystemWalletId(string $walletName, \Illuminate\Database\Eloquent\Collection $wallets): int
    {
        $match = $wallets->first(fn($wallet) => $wallet->group_type === 'System' && $wallet->name === $walletName);

        if (!$match) {
            throw new WalletNotFoundException("Dompet Sistem '{$walletName}' tidak ditemukan.");
        }

        return $match->id;
    }

    // ════════════════════════════════════════════════════════════════
    // MULTI TRANSACTION — partial success, ordered results
    // ════════════════════════════════════════════════════════════════

    /**
     * Proses setiap item dalam batch secara independen.
     * Satu item gagal TIDAK membatalkan item lain.
     * Mengembalikan MultiTransactionResult yang platform-agnostic —
     * formatting untuk Telegram/Web dilakukan di layer controller.
     */
    private function processMulti(
        User $user, string $text,
        array $wallets, array $categories, array $activeMemories,
        string $source
    ): array {
        // 1. Pastikan ada LLM yang aktif — multi-transaction tidak bisa pakai Python
        $preference = $this->preferenceManager->getActivePreference($user);
        if (!$preference) {
            Log::info("MultiTx: No LLM configured for user #{$user->id}, fallback to single parse.");
            return $this->processSingle($user, $text, $wallets, $categories, $activeMemories, $source);
        }

        $credential = $this->credentialManager->getCredential($user, $preference->provider);
        if (!$credential || blank($credential->api_key) || !$credential->is_valid) {
            throw new AiConfigurationException("API Key untuk '{$preference->provider->value}' bermasalah.");
        }

        $provider = $this->providerFactory->make($preference->provider);
        $model    = $preference->selected_model ?? $preference->provider->defaultModel();

        $llmRequest = new AiProviderRequest(
            text:           $text,
            apiKey:         $credential->api_key,
            model:          $model,
            wallets:        $wallets,
            categories:     $categories,
            activeMemories: $activeMemories,
        );

        // 2. Parse multi-transaction via LLM
        $multiResult = $provider->parseMultiTransaction($llmRequest);

        if (!$multiResult->success || empty($multiResult->transactions)) {
            return [
                'success' => false,
                'message' => "❌ AI Gagal memproses multi-transaksi: " . ($multiResult->error ?? "Format tidak dikenali."),
            ];
        }

        // Catat token usage
        if (!empty($multiResult->usage['total'])) {
            \App\Models\AiUsageLog::create([
                'user_id'           => $user->id,
                'provider'          => $preference->provider->value,
                'model'             => $model,
                'prompt_tokens'     => $multiResult->usage['prompt'],
                'completion_tokens' => $multiResult->usage['completion'],
                'total_tokens'      => $multiResult->usage['total'],
            ]);
        }

        // 3. Proses tiap transaksi secara independen, urutan dipertahankan.
        //    ProcessTransactionAction::create() sudah membungkus DB::transaction sendiri,
        //    jadi isolasi per-item sudah dijamin di level Action.
        $threshold = (float) config('bendaharaku.ai.confidence.threshold_auto_clear', 0.85);
        $results   = [];   // MultiTransactionItem[], urutan = urutan input

        foreach ($multiResult->transactions as $idx => $parsed) {
            $num = $idx + 1;
            // Gunakan notes dari AI sebagai representasi teks asli per item.
            // Notes berisi teks yang diparse AI (mis. "bensin 20k cash").
            $rawText = $parsed->notes ?? "Transaksi #{$num}";

            // ── Guard: nominal ────────────────────────────────────
            if (!$parsed->amount || $parsed->amount <= 0) {
                $results[] = MultiTransactionItem::failed(
                    index:     $num,
                    raw:       $rawText,
                    errorCode: MultiTransactionErrorCode::INVALID_AMOUNT,
                    reason:    'Nominal tidak valid atau nol.',
                );
                Log::warning("MultiTx #{$num}: nominal tidak valid.", [
                    'user_id' => $user->id,
                    'raw'     => $rawText,
                    'parsed'  => (array) $parsed,
                ]);
                continue;
            }

            // ── Guard: kategori ───────────────────────────────────
            if (!$parsed->category) {
                $results[] = MultiTransactionItem::failed(
                    index:     $num,
                    raw:       $rawText,
                    errorCode: MultiTransactionErrorCode::CATEGORY_NOT_FOUND,
                    reason:    'Kategori tidak terdeteksi oleh AI.',
                );
                Log::warning("MultiTx #{$num}: kategori kosong.", [
                    'user_id' => $user->id,
                    'raw'     => $rawText,
                    'parsed'  => (array) $parsed,
                ]);
                continue;
            }

            // ── Resolve & simpan ──────────────────────────────────
            try {
                $resolved = $this->resolver->resolve($user, $parsed);

                $resolved = new ResolvedTransaction(
                    amount:              $resolved->amount,
                    categoryId:          $resolved->categoryId,
                    sourceWalletId:      $resolved->sourceWalletId,
                    destinationWalletId: $resolved->destinationWalletId,
                    subject:             $resolved->subject ?? $user->name,
                    notes:               $rawText,
                    isCleared:           ($multiResult->confidence >= $threshold),
                );

                // ProcessTransactionAction::create() punya DB::transaction sendiri.
                // Jika melempar exception, hanya item ini yang gagal.
                $log = $this->transactionAction->create([
                    'date'                  => now()->format('Y-m-d'),
                    'category_id'           => $resolved->categoryId,
                    'source_wallet_id'      => $resolved->sourceWalletId,
                    'destination_wallet_id' => $resolved->destinationWalletId,
                    'amount'                => $resolved->amount,
                    'subject'               => $resolved->subject,
                    'notes'                 => $resolved->notes,
                    'is_cleared'            => $resolved->isCleared,
                ], $user->id, $source);

                $results[] = MultiTransactionItem::success(
                    index:       $num,
                    transaction: $log->load(['category', 'sourceWallet', 'destinationWallet', 'type']),
                    raw:         $rawText,
                );

            } catch (WalletNotFoundException $e) {
                $results[] = MultiTransactionItem::failed(
                    index:     $num,
                    raw:       $rawText,
                    errorCode: MultiTransactionErrorCode::WALLET_NOT_FOUND,
                    reason:    $e->getMessage(),
                );
                Log::warning("MultiTx #{$num}: wallet tidak ditemukan.", [
                    'user_id'  => $user->id,
                    'raw'      => $rawText,
                    'reason'   => $e->getMessage(),
                    'parsed'   => (array) $parsed,
                ]);

            } catch (CategoryNotFoundException $e) {
                $results[] = MultiTransactionItem::failed(
                    index:     $num,
                    raw:       $rawText,
                    errorCode: MultiTransactionErrorCode::CATEGORY_NOT_FOUND,
                    reason:    $e->getMessage(),
                );
                Log::warning("MultiTx #{$num}: kategori tidak ditemukan.", [
                    'user_id'  => $user->id,
                    'raw'      => $rawText,
                    'reason'   => $e->getMessage(),
                    'parsed'   => (array) $parsed,
                ]);

            } catch (InvalidArgumentException $e) {
                // Tangkap SAME_WALLET dan INVALID_AMOUNT dari ProcessTransactionAction
                $message   = $e->getMessage();
                $errorCode = str_contains($message, 'sama')
                    ? MultiTransactionErrorCode::SAME_WALLET
                    : MultiTransactionErrorCode::VALIDATION_ERROR;

                $results[] = MultiTransactionItem::failed(
                    index:     $num,
                    raw:       $rawText,
                    errorCode: $errorCode,
                    reason:    $message,
                );
                Log::warning("MultiTx #{$num}: validation error.", [
                    'user_id'     => $user->id,
                    'raw'         => $rawText,
                    'error_code'  => $errorCode->value,
                    'reason'      => $message,
                    'parsed'      => (array) $parsed,
                ]);

            } catch (RuntimeException $e) {
                // Saldo tidak mencukupi dari applyTransaction()
                $results[] = MultiTransactionItem::failed(
                    index:     $num,
                    raw:       $rawText,
                    errorCode: MultiTransactionErrorCode::INSUFFICIENT_BALANCE,
                    reason:    $e->getMessage(),
                );
                Log::warning("MultiTx #{$num}: saldo tidak mencukupi.", [
                    'user_id'  => $user->id,
                    'raw'      => $rawText,
                    'reason'   => $e->getMessage(),
                    'parsed'   => (array) $parsed,
                ]);

            } catch (Throwable $e) {
                $results[] = MultiTransactionItem::failed(
                    index:     $num,
                    raw:       $rawText,
                    errorCode: MultiTransactionErrorCode::UNKNOWN_ERROR,
                    reason:    'Terjadi error tidak terduga.',
                );
                Log::error("MultiTx #{$num}: unknown error.", [
                    'user_id'   => $user->id,
                    'raw'       => $rawText,
                    'exception' => $e,
                    'parsed'    => (array) $parsed,
                ]);
            }
        }

        // 4. Bangun MultiTransactionResult
        $multiTxResult = new MultiTransactionResult(
            results:    $results,
            provider:   $multiResult->provider,
            model:      $multiResult->model,
            confidence: $multiResult->confidence,
        );

        // Log ringkas untuk debugging batch
        Log::info("MultiTx selesai.", [
            'user_id' => $user->id,
            'summary' => $multiTxResult->summary(),
            'failed'  => array_map(
                fn (MultiTransactionItem $i) => [
                    'index'      => $i->index,
                    'raw'        => $i->raw,
                    'error_code' => $i->errorCode?->value,
                    'reason'     => $i->reason,
                ],
                $multiTxResult->failedItems()
            ),
        ]);

        // 5. Catat ke ai_parse_logs agar riwayat muncul di AI Analytics
        //    (Token sudah tercatat di AiUsageLog, ini untuk riwayat request/parse)
        $this->parseLogService->createMultiLog(
            user:         $user,
            inputText:    $text,
            provider:     $multiResult->provider,
            model:        $multiResult->model,
            confidence:   $multiResult->confidence,
            successCount: $multiTxResult->successCount(),
            totalCount:   $multiTxResult->totalCount(),
            usage:        $multiResult->usage,
        );

        // success=true jika minimal satu transaksi berhasil
        return [
            'success'      => $multiTxResult->hasAnySuccess(),
            'is_multi'     => true,
            'multi_result' => $multiTxResult,
        ];
    }
}
