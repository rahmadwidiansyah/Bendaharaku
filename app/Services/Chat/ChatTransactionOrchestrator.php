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
use App\Enums\TransactionIntent;
use App\Events\TransactionPosted;
use App\Exceptions\CategoryNotFoundException;
use App\Exceptions\WalletNotFoundException;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiProviderException;
use App\DTO\ConfidenceScoreContext;
use Illuminate\Support\Facades\Log;
use Throwable;
use InvalidArgumentException;

class ChatTransactionOrchestrator
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly TransactionResolver $resolver,
        private readonly ProcessTransactionAction $transactionAction,
        // Injeksi komponen Sprint 4E
        private readonly UserMemoryService $memoryService,
        private readonly ConfidenceScoringEngine $scoringEngine,
        private readonly AiParseLogService $parseLogService
    ) {}

    /**
     * @return array{success: bool, message: string, transaction?: TransactionLog}
     */
    public function process(User $user, string $text, string $source = 'TEL'): array
    {
        try {
            // 1. Ambil Konteks (Sekarang butuh keyword dan group_type untuk Scoring Engine)
            $wallets = $user->wallets()->get(['id', 'name', 'group_type', 'keyword'])->toArray();
            $categories = $user->categories()->get(['id', 'category_name', 'type_id', 'keyword'])->toArray();

            // 2. Tarik Memori Personal (Decay On Read) — WARN-01 fix: tambahkan $text
            $activeMemories = $this->memoryService->getTopRelevantMemories($user->id, $text);

            // 3. AI Layer (Prompting + Parsing)
            $aiResult = $this->aiManager->parseTransaction($user, $text, $wallets, $categories, $activeMemories);

            if (!$aiResult->success || !$aiResult->transaction) {
                return [
                    'success' => false,
                    'message' => "❌ AI Gagal memproses: " . ($aiResult->error ?? "Format tidak dikenali.")
                ];
            }

            $parsed = $aiResult->transaction;

            // 4. Validasi Dasar (Dipertahankan dari kode aslimu)
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
                return [
                    'success' => false, 
                    'message' => "🤝 *Nama orangnya siapa Bos?*\nKarena ini transaksi Hutang/Piutang, kamu WAJIB pakai hashtag buat nyebut nama orangnya.\n\n💡 *Contoh:* pinjam uang 50k dana #Budi"
                ];
            }

            $finalSubject = $extractedSubject ?? $user->name;

            // 5. Resolving Layer dengan Fallback DRAFT
            // BUG-02 fix: $finalConfidence wajib dideklarasikan SEBELUM try-catch agar
            // tidak undefined di dalam catch block.
            $finalConfidence = 0.0;
            try {
                $resolved = $this->resolver->resolve($user, $parsed);
                // 6. Confidence Scoring Engine
                // ... hitung skor final ...
                $resolved->isCleared = ($finalConfidence >= $threshold);
            } catch (CategoryNotFoundException | WalletNotFoundException $e) {
                // FALLBACK: Paksa jadi DRAFT jika kategori/dompet ngawur, JANGAN di-return false!
                $resolved = new \App\DTO\ResolvedTransaction(
                    amount: $parsed->amount,
                    categoryId: null, // Kosong agar diedit manual
                    sourceWalletId: null, 
                    destinationWalletId: null,
                    subject: $finalSubject,
                    notes: $text . "\n[ERROR AI: " . $e->getMessage() . "]",
                    isCleared: false // HARD DRAFT
                );
                
                $finalConfidence = 0.0; // Skor jatuh ke 0 — eksplisit
            }
            
            // ... (Lanjut ke Parse Logging dan Simpan Database)
            // 6. Confidence Scoring Engine Layer
            $scoreContext = new ConfidenceScoreContext(
                user: $user,
                inputText: $text,
                parseResult: $aiResult,
                resolvedTransaction: $resolved,
                activeMemories: $activeMemories
            );

            $finalConfidence = $this->scoringEngine->calculateFinalScore($scoreContext);
            $threshold = (float) config('bendaharaku.ai.confidence.threshold_auto_clear', 0.85);
            
            // Timpa status isCleared dengan hasil hitung matematis dari Scoring Engine
            $resolved->isCleared = ($finalConfidence >= $threshold);

            // 7. Parse Logging (Sinkronus - Catat Kejujuran AI vs Hasil Sistem)
            $parseLogId = $this->parseLogService->createLog(
                user: $user,
                inputText: $text,
                result: $aiResult,
                finalConfidence: $finalConfidence
            );

            // 8. DB Action Layer
            $actionData = [
                'date'                  => now()->format('Y-m-d'),
                'category_id'           => $resolved->categoryId,
                'source_wallet_id'      => $resolved->sourceWalletId,
                'destination_wallet_id' => $resolved->destinationWalletId,
                'amount'                => $resolved->amount,
                'subject'               => $finalSubject,
                'notes'                 => $text . ($resolved->isCleared ? '' : ' [DRAFT AI]'),
                'is_cleared'            => $resolved->isCleared,
            ];

            $transactionLog = $this->transactionAction->create($actionData, $user->id, $source);

            // 9. Trigger Event Learning (Asinkronus / Menuju Memori & Dataset)
            event(new TransactionPosted($user, $transactionLog, $parseLogId));

            return [
                'success' => true,
                'message' => 'Transaksi berhasil dicatat.',
                'transaction' => $transactionLog->load(['category', 'sourceWallet', 'destinationWallet', 'type'])
            ];

        } catch (CategoryNotFoundException | WalletNotFoundException $e) {
            // Tangkap Error dari Resolver agar Telegram bisa membalas dengan rapi
            return [
                'success' => false,
                'message' => "🔍 *Data Tidak Ditemukan:*\n" . $e->getMessage() . "\n\nPastikan keyword dompet/kategori sudah kamu daftarkan di Web."
            ];
        } catch (AiConfigurationException $e) {
            Log::warning('AI tidak dikonfigurasi', ['user_id' => $user->id, 'message' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => implode("\n", [
                    "⚙️ *AI Belum Dikonfigurasi*",
                    "",
                    "Python sedang offline dan belum ada AI cadangan yang aktif.",
                    "",
                    "👉 Buka *Dashboard Web → Settings → AI* lalu centang *\"Aktifkan sebagai AI Cadangan\"* pada provider yang sudah kamu isi API key-nya.",
                ])
            ];
        } catch (AiRateLimitException $e) {
            $provider = $e->getMessage(); // Message berisi nama provider
            Log::warning("Rate limit tercapai", ['provider' => $provider, 'user_id' => $user->id]);
            return [
                'success' => false,
                'message' => implode("\n", [
                    "⚠️ *Kuota API {$provider} Habis*",
                    "",
                    "Limit token/request harian kamu sudah tercapai. Transaksi ini tidak bisa diproses sementara.",
                    "",
                    "💡 *Solusi:*",
                    "• Tunggu reset kuota (biasanya tengah malam)",
                    "• Atau topup/upgrade plan API kamu di dashboard {$provider}",
                ])
            ];
        } catch (AiTimeoutException $e) {
            $provider = $e->getMessage();
            Log::warning("Provider timeout", ['provider' => $provider, 'user_id' => $user->id]);
            return [
                'success' => false,
                'message' => implode("\n", [
                    "⏳ *Server {$provider} Sedang Sibuk*",
                    "",
                    "Request ke AI {$provider} timeout. Coba kirim ulang pesanmu dalam 1-2 menit ya Bos.",
                ])
            ];
        } catch (AiProviderException $e) {
            Log::error('AI provider error', ['user_id' => $user->id, 'message' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => implode("\n", [
                    "❌ *Terjadi Error pada AI*",
                    "",
                    "`" . $e->getMessage() . "`",
                    "",
                    "Coba lagi nanti. Jika terus berulang, cek API key kamu di *Settings → AI*.",
                ])
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => "⚠️ *Gagal diproses:*\n" . $e->getMessage()
            ];
        } catch (Throwable $e) {
            Log::error('Chat Orchestrator Error', [
                'user_id'   => $user->id,
                'text'      => $text,
                'exception' => $e,
                'message'   => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => "❌ Waduh, ada error sistem internal Bos. Coba lagi nanti ya."
            ];
        }
    }
}