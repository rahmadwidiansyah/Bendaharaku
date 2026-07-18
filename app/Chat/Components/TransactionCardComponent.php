<?php

declare(strict_types=1);

namespace App\Chat\Components;

use App\Models\TransactionLog;

/**
 * Kartu detail satu transaksi.
 *
 * Membawa data terstruktur dari TransactionLog.
 * Formatter memutuskan tampilannya:
 * - Telegram  : formatted text dengan emoji + label
 * - Web       : card UI dengan badge tipe, amount besar, wallet chip
 * - Discord   : embed dengan fields
 *
 * $index — urutan dalam batch (null = single transaction)
 */
readonly class TransactionCardComponent implements ChatComponentInterface
{
    public function __construct(
        public TransactionLog $transaction,
        public ?int           $index       = null,
        public bool           $showDetails = true,
    ) {}

    public function type(): string
    {
        return 'transaction_card';
    }

    public function toArray(): array
    {
        $trx = $this->transaction;
        return [
            'type'         => $this->type(),
            'index'        => $this->index,
            'show_details' => $this->showDetails,
            'transaction'  => [
                'id'               => $trx->id,
                'reference_number' => $trx->reference_number,
                'amount'           => $trx->amount,
                'is_cleared'       => $trx->is_cleared,
                'category'         => $trx->category?->category_name,
                'type'             => $trx->type?->name,
                'source_wallet'    => $trx->sourceWallet?->name,
                'dest_wallet'      => $trx->destinationWallet?->name,
                'subject'          => $trx->subject,
                'notes'            => $trx->notes,
                'date'             => $trx->date,
            ],
        ];
    }
}
