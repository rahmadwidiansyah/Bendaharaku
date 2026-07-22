<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Actions\ProcessTransactionAction;
use App\Enums\EvidenceStatus;
use App\Evidence\DTO\TransactionDraft;
use App\Models\Evidence;
use App\Models\TransactionLog;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * EvidenceCommitService — Commit draft transaksi dari evidence ke transaction_logs.
 *
 * Tanggung jawab:
 * - Membaca TransactionDraft dari evidence
 * - Memvalidasi draft terakhir
 * - Memanggil ProcessTransactionAction::create()
 * - Menghubungkan Evidence dengan Transaction
 * - Mengubah status menjadi COMPLETED
 *
 * JANGAN membuat logika transaksi baru — gunakan ProcessTransactionAction yang sudah ada.
 */
class EvidenceCommitService
{
    public function __construct(
        private readonly ProcessTransactionAction $transactionAction,
    ) {}

    /**
     * Commit evidence draft menjadi transaksi nyata.
     *
     * @return array{success: bool, transaction_id?: int, status?: string, message?: string, warnings?: array}
     */
    public function commit(Evidence $evidence, array $overrides = []): array
    {
        // ── 1. Validasi status ────────────────────────────────────────
        if (! $evidence->isReady() && ! $evidence->isResolved()) {
            return [
                'success' => false,
                'message' => 'Evidence belum siap untuk di-commit.',
            ];
        }

        // ── 2. Cek apakah sudah di-commit (idempotency) ──────────────
        if ($evidence->transaction_id !== null) {
            $existingTransaction = TransactionLog::find($evidence->transaction_id);
            if ($existingTransaction) {
                Log::info('Evidence already committed', [
                    'evidence_id' => $evidence->id,
                    'transaction_id' => $evidence->transaction_id,
                ]);

                return [
                    'success' => true,
                    'transaction_id' => $evidence->transaction_id,
                    'status' => 'COMPLETED',
                    'message' => 'Transaksi sudah dibuat sebelumnya.',
                ];
            }
        }

        // ── 3. Ambil draft data ──────────────────────────────────────
        $draft = $evidence->resolved_data;

        if (! $draft) {
            return [
                'success' => false,
                'message' => 'Draft data tidak tersedia.',
            ];
        }

        // ── 4. Apply overrides dari user review ──────────────────────
        $data = $this->applyOverrides($draft, $overrides);

        // ── 5. Duplicate check ───────────────────────────────────────
        $duplicateCheck = $this->checkDuplicate($evidence->user_id, $data);
        if ($duplicateCheck['is_duplicate']) {
            Log::warning('Potential duplicate detected during commit', [
                'evidence_id' => $evidence->id,
                'warnings' => $duplicateCheck['warnings'],
            ]);
            // User tetap boleh melanjutkan — hanya log warning
        }

        // ── 6. Map draft ke format ProcessTransactionAction ──────────
        $transactionData = $this->mapDraftToTransactionData($data, $evidence);

        // ── 7. Commit dalam DB transaction ───────────────────────────
        try {
            $transaction = DB::transaction(function () use ($transactionData, $evidence) {
                // Buat transaksi menggunakan action yang sudah ada
                $transactionLog = $this->transactionAction->create(
                    data: $transactionData,
                    userId: $evidence->user_id,
                    sourcePrefix: 'OCR',
                );

                // Hubungkan evidence dengan transaction
                $evidence->update([
                    'status' => EvidenceStatus::Completed,
                    'transaction_id' => $transactionLog->id,
                    'completed_at' => now(),
                    'commit_version' => config('evidence.pipeline_version', '1.0'),
                ]);

                return $transactionLog;
            });

            Log::info('Evidence committed successfully', [
                'evidence_id' => $evidence->id,
                'uuid' => $evidence->uuid,
                'transaction_id' => $transaction->id,
                'reference_number' => $transaction->reference_number,
                'amount' => $transaction->amount,
            ]);

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'status' => 'COMPLETED',
                'message' => 'Transaksi berhasil dibuat.',
                'transaction' => $transaction->load(['category', 'sourceWallet', 'destinationWallet']),
                'warnings' => $duplicateCheck['warnings'] ?? [],
            ];

        } catch (\Throwable $e) {
            Log::error('Evidence commit failed', [
                'evidence_id' => $evidence->id,
                'uuid' => $evidence->uuid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal membuat transaksi: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Apply overrides dari user review ke draft.
     */
    private function applyOverrides(TransactionDraft $draft, array $overrides): TransactionDraft
    {
        if (empty($overrides)) {
            return $draft;
        }

        return new TransactionDraft(
            transactionType: $overrides['transaction_type'] ?? $draft->transactionType,
            walletId: $overrides['wallet_id'] ?? $draft->walletId,
            walletName: $overrides['wallet_name'] ?? $draft->walletName,
            categoryId: $overrides['category_id'] ?? $draft->categoryId,
            categoryName: $overrides['category_name'] ?? $draft->categoryName,
            merchantName: $draft->merchantName,
            amount: $overrides['amount'] ?? $draft->amount,
            currency: $draft->currency,
            description: $overrides['description'] ?? $draft->description,
            transactionDate: $overrides['transaction_date'] ?? $draft->transactionDate,
            referenceNumber: $draft->referenceNumber,
            destinationName: $overrides['destination_name'] ?? $draft->destinationName,
            destinationAccount: $overrides['destination_account'] ?? $draft->destinationAccount,
            destinationWalletId: $draft->destinationWalletId,
            confidence: $draft->confidence,
            warnings: $draft->warnings,
            metadata: $draft->metadata,
            resolved: true,
            amountConfidence: $draft->amountConfidence,
            walletConfidence: $draft->walletConfidence,
            categoryConfidence: $draft->categoryConfidence,
            destinationNameConfidence: $draft->destinationNameConfidence,
            destinationAccountConfidence: $draft->destinationAccountConfidence,
            dateConfidence: $draft->dateConfidence,
            referenceConfidence: $draft->referenceConfidence,
        );
    }

    /**
     * Check for potential duplicates before commit.
     */
    private function checkDuplicate(int $userId, TransactionDraft $draft): array
    {
        $warnings = [];
        $isDuplicate = false;

        // Check by reference number
        if ($draft->referenceNumber) {
            $existing = TransactionLog::where('user_id', $userId)
                ->where('reference_number', $draft->referenceNumber)
                ->where('is_cleared', true)
                ->exists();

            if ($existing) {
                $warnings[] = "Referensi {$draft->referenceNumber} sudah ada di transaksi sebelumnya.";
                $isDuplicate = true;
            }
        }

        // Check by amount + wallet + date (±5 minutes)
        if ($draft->amount > 0 && $draft->walletId && $draft->transactionDate) {
            try {
                $date = Carbon::parse($draft->transactionDate);
                $windowStart = $date->copy()->subMinutes(5);
                $windowEnd = $date->copy()->addMinutes(5);

                $existing = TransactionLog::where('user_id', $userId)
                    ->where('amount', $draft->amount)
                    ->where(function ($q) use ($draft) {
                        $q->where('source_wallet_id', $draft->walletId)
                            ->orWhere('destination_wallet_id', $draft->walletId);
                    })
                    ->where('is_cleared', true)
                    ->whereBetween('date', [$windowStart, $windowEnd])
                    ->exists();

                if ($existing) {
                    $warnings[] = 'Kemungkinan duplikat: nominal dan dompet yang sama dalam ±5 menit.';
                    $isDuplicate = true;
                }
            } catch (\Throwable) {
                // Date parsing failed — skip
            }
        }

        return [
            'is_duplicate' => $isDuplicate,
            'warnings' => $warnings,
        ];
    }

    /**
     * Map TransactionDraft ke format yang dibutuhkan ProcessTransactionAction::create().
     */
    private function mapDraftToTransactionData(TransactionDraft $draft, Evidence $evidence): array
    {
        // Resolve source and destination wallet IDs based on transaction type
        // Following the same pattern as TransactionResolver in AI service
        $sourceWalletId = $draft->walletId;
        $destinationWalletId = $draft->destinationWalletId;

        // EXPENSE: user wallet → Merchant System
        if ($draft->transactionType === 'EXPENSE') {
            $sourceWalletId = $draft->walletId;
            $destinationWalletId = $this->getSystemWalletId(
                $evidence->user_id,
                config('bendaharaku.system_wallets.merchant', 'Merchant System')
            );
        }

        // INCOME: External System → user wallet
        elseif ($draft->transactionType === 'INCOME') {
            $sourceWalletId = $this->getSystemWalletId(
                $evidence->user_id,
                config('bendaharaku.system_wallets.external', 'External System')
            );
            $destinationWalletId = $draft->walletId;
        }

        // TRANSFER: user wallet → user wallet (already resolved in draft)
        elseif ($draft->transactionType === 'TRANSFER' || $draft->transactionType === 'INTERNAL_TRANSFER') {
            $sourceWalletId = $draft->walletId;
            $destinationWalletId = $draft->destinationWalletId
                ?? throw new \RuntimeException('Transfer requires destination wallet ID');
        }

        // DEBT & RECEIVABLE: handled by category system_key, but basic mapping:
        // For now, use destination if provided, otherwise use system wallet
        else {
            if ($destinationWalletId === null) {
                $destinationWalletId = $this->getSystemWalletId(
                    $evidence->user_id,
                    config('bendaharaku.system_wallets.external', 'External System')
                );
            }
        }

        if ($sourceWalletId === null || $destinationWalletId === null) {
            throw new \RuntimeException('Failed to resolve source or destination wallet');
        }

        return [
            'transaction_type' => $draft->transactionType,
            'category_id' => $draft->categoryId,
            'source_wallet_id' => $sourceWalletId,
            'destination_wallet_id' => $destinationWalletId,
            'amount' => $draft->amount,
            'date' => $draft->transactionDate ?? now()->format('Y-m-d'),
            'subject' => $draft->destinationName ?? $draft->merchantName ?? '-',
            'notes' => $this->buildNotes($draft, $evidence),
            'is_cleared' => true,
        ];
    }

    /**
     * Get system wallet ID by name for the user.
     */
    private function getSystemWalletId(int $userId, string $systemWalletName): ?int
    {
        $systemWallet = Wallet::where('user_id', $userId)
            ->where('group_type', 'System')
            ->where('name', $systemWalletName)
            ->first();

        return $systemWallet?->id;
    }

    /**
     * Build notes from draft and evidence metadata.
     */
    private function buildNotes(TransactionDraft $draft, Evidence $evidence): string
    {
        $parts = [];

        if ($draft->description) {
            $parts[] = $draft->description;
        }

        if ($draft->referenceNumber) {
            $parts[] = "Ref: {$draft->referenceNumber}";
        }

        $parts[] = "[OCR: {$evidence->original_name}]";

        return implode(' | ', $parts);
    }
}
