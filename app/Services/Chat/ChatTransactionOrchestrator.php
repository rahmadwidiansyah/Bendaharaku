<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\User;
use App\Models\TransactionLog;
use App\Services\AI\AIManager;
use App\Services\AI\TransactionResolver;
use App\Actions\ProcessTransactionAction;
use App\Enums\TransactionIntent;
use Illuminate\Support\Facades\Log;
use Throwable;
use InvalidArgumentException;

class ChatTransactionOrchestrator
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly TransactionResolver $resolver,
        private readonly ProcessTransactionAction $transactionAction
    ) {}

    /**
     * @return array{success: bool, message: string, transaction?: TransactionLog}
     */
    public function process(User $user, string $text, string $source = 'TEL'): array
    {
        try {
            // Efisiensi Query menggunakan relasi
            $wallets = $user->wallets()->get(['name'])->toArray();
            $categories = $user->categories()->get(['category_name'])->toArray();

            $aiResult = $this->aiManager->parseTransaction($user, $text, $wallets, $categories);

            if (!$aiResult->success || !$aiResult->transaction) {
                return [
                    'success' => false,
                    'message' => "❌ AI Gagal memproses: " . ($aiResult->error ?? "Format tidak dikenali.")
                ];
            }

            $parsed = $aiResult->transaction;

            if (!$parsed->amount) {
                return ['success' => false, 'message' => "🤔 *Nominalnya berapa Bos?*\nAku bingung nih, kamu belum nyebutin jumlah uangnya."];
            }
            if (!$parsed->category) {
                return ['success' => false, 'message' => "🧐 *Masuk kategori apa nih?*\nSebutkan nama barang atau kegiatannya ya."];
            }

            $resolved = $this->resolver->resolve($user, $parsed);

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

            // Tidak ada update dua kali, prefix langsung dilempar
            $transactionLog = $this->transactionAction->create($actionData, $user->id, $source);

            // Load 'type' untuk kebutuhan visualisasi di controller nanti
            return [
                'success' => true,
                'message' => 'Transaksi berhasil dicatat.',
                'transaction' => $transactionLog->load(['category', 'sourceWallet', 'destinationWallet', 'type'])
            ];

        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => "⚠️ *Gagal diproses:*\n" . $e->getMessage()
            ];
        } catch (Throwable $e) {
            Log::error('Chat Orchestrator Error', [
                'user_id' => $user->id,
                'text' => $text,
                'exception' => $e,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => "❌ Waduh, ada error sistem internal Bos. Coba lagi nanti ya."
            ];
        }
    }
}