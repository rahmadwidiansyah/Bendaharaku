<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\MultiTransactionErrorCode;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;

/**
 * Satu item dalam results[] dari MultiTransactionResult.
 *
 * Bisa berupa item sukses (status='success', $transaction terisi),
 * item draft (status='success', $draft terisi, $transaction null), atau
 * item gagal (status='failed', $errorCode dan $reason terisi).
 *
 * Gunakan named constructor ::success(), ::successDraft(), dan ::failed() agar jelas.
 */
readonly class MultiTransactionItem
{
    private function __construct(
        /** Urutan item sesuai input user (1-based) */
        public int $index,

        /** 'success' atau 'failed' */
        public string $status,

        /** Terisi jika status='success' dan ini bukan draft */
        public ?TransactionLog $transaction,

        /** Terisi jika status='success' dan ini adalah WEB draft */
        public ?TransactionDraft $draft,

        /** Teks input asli (dari parsed->notes atau teks fallback) */
        public ?string $raw,

        /** Kode error standar, terisi jika status='failed' */
        public ?MultiTransactionErrorCode $errorCode,

        /** Pesan error human-readable, terisi jika status='failed' */
        public ?string $reason,
    ) {}

    // ── Named constructors ────────────────────────────────────────

    public static function success(int $index, TransactionLog $transaction, string $raw): self
    {
        return new self(
            index: $index,
            status: 'success',
            transaction: $transaction,
            draft: null,
            raw: $raw,
            errorCode: null,
            reason: null,
        );
    }

    /**
     * Named constructor untuk item yang berhasil disimpan sebagai WEB draft.
     * Digunakan oleh processMulti() saat source = WEB.
     */
    public static function successDraft(int $index, TransactionDraft $draft, string $raw): self
    {
        return new self(
            index: $index,
            status: 'success',
            transaction: null,
            draft: $draft,
            raw: $raw,
            errorCode: null,
            reason: null,
        );
    }

    public static function failed(
        int $index,
        string $raw,
        MultiTransactionErrorCode $errorCode,
        string $reason,
    ): self {
        return new self(
            index: $index,
            status: 'failed',
            transaction: null,
            draft: null,
            raw: $raw,
            errorCode: $errorCode,
            reason: $reason,
        );
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Apakah item ini merupakan WEB draft (bukan TransactionLog).
     */
    public function isDraft(): bool
    {
        return $this->isSuccess() && $this->draft !== null;
    }

    /**
     * Serialize ke array untuk JSON response / logging.
     */
    public function toArray(): array
    {
        if ($this->isSuccess()) {
            // WEB Draft item
            if ($this->isDraft()) {
                $draft = $this->draft;
                $payload = $draft->payload ?? [];

                return [
                    'index' => $this->index,
                    'status' => 'success',
                    'raw' => $this->raw,
                    'is_draft' => true,
                    'draft' => [
                        'id' => $draft->id,
                        'amount' => $payload['amount'] ?? 0,
                        'category' => $payload['category_name'] ?? null,
                        'source_wallet' => $payload['source_wallet_name'] ?? null,
                        'dest_wallet' => $payload['destination_wallet_name'] ?? null,
                        'type_key' => $payload['type_key'] ?? 'expense',
                        'is_cleared' => false,
                        'needs_wallet' => $payload['needs_wallet'] ?? false,
                    ],
                ];
            }

            // TransactionLog item
            $trx = $this->transaction;

            return [
                'index' => $this->index,
                'status' => 'success',
                'raw' => $this->raw,
                'is_draft' => false,
                'transaction' => [
                    'id' => $trx->id,
                    'reference_number' => $trx->reference_number,
                    'amount' => $trx->amount,
                    'category' => $trx->category?->category_name,
                    'source_wallet' => $trx->sourceWallet?->name,
                    'dest_wallet' => $trx->destinationWallet?->name,
                    'type' => $trx->type?->name,
                    'is_cleared' => $trx->is_cleared,
                ],
            ];
        }

        return [
            'index' => $this->index,
            'status' => 'failed',
            'raw' => $this->raw,
            'error_code' => $this->errorCode?->value,
            'reason' => $this->reason,
        ];
    }
}
