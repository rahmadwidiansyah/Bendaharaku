<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\MultiTransactionErrorCode;
use App\Models\TransactionLog;

/**
 * Satu item dalam results[] dari MultiTransactionResult.
 *
 * Bisa berupa item sukses (status='success', $transaction terisi) atau
 * item gagal (status='failed', $errorCode dan $reason terisi).
 *
 * Gunakan named constructor ::success() dan ::failed() agar jelas.
 */
readonly class MultiTransactionItem
{
    private function __construct(
        /** Urutan item sesuai input user (1-based) */
        public int                         $index,

        /** 'success' atau 'failed' */
        public string                      $status,

        /** Terisi jika status='success' */
        public ?TransactionLog             $transaction,

        /** Teks input asli (dari parsed->notes atau teks fallback) */
        public ?string                     $raw,

        /** Kode error standar, terisi jika status='failed' */
        public ?MultiTransactionErrorCode  $errorCode,

        /** Pesan error human-readable, terisi jika status='failed' */
        public ?string                     $reason,
    ) {}

    // ── Named constructors ────────────────────────────────────────

    public static function success(int $index, TransactionLog $transaction, string $raw): self
    {
        return new self(
            index:       $index,
            status:      'success',
            transaction: $transaction,
            raw:         $raw,
            errorCode:   null,
            reason:      null,
        );
    }

    public static function failed(
        int                        $index,
        string                     $raw,
        MultiTransactionErrorCode  $errorCode,
        string                     $reason,
    ): self {
        return new self(
            index:       $index,
            status:      'failed',
            transaction: null,
            raw:         $raw,
            errorCode:   $errorCode,
            reason:      $reason,
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
     * Serialize ke array untuk JSON response / logging.
     */
    public function toArray(): array
    {
        if ($this->isSuccess()) {
            $trx = $this->transaction;
            return [
                'index'       => $this->index,
                'status'      => 'success',
                'raw'         => $this->raw,
                'transaction' => [
                    'id'               => $trx->id,
                    'reference_number' => $trx->reference_number,
                    'amount'           => $trx->amount,
                    'category'         => $trx->category?->category_name,
                    'source_wallet'    => $trx->sourceWallet?->name,
                    'dest_wallet'      => $trx->destinationWallet?->name,
                    'type'             => $trx->type?->name,
                    'is_cleared'       => $trx->is_cleared,
                ],
            ];
        }

        return [
            'index'      => $this->index,
            'status'     => 'failed',
            'raw'        => $this->raw,
            'error_code' => $this->errorCode?->value,
            'reason'     => $this->reason,
        ];
    }
}
