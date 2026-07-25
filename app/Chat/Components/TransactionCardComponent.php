<?php

declare(strict_types=1);

namespace App\Chat\Components;

use App\Enums\TransactionIntent;
use App\Models\TransactionLog;
use App\Support\MoneyFormatter;

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
 *
 * needs_wallet = true  → wallet belum dipilih (draft), tampilkan QuickWalletPicker
 * needs_wallet = false → wallet sudah ada
 *
 * $draftId — jika tidak null, komponen ini merepresentasikan TransactionDraft
 * (bukan TransactionLog). WebFormatter akan menggunakan draftId sebagai 'id'
 * di output JSON sehingga frontend tahu ini adalah draft ID.
 */
readonly class TransactionCardComponent implements ChatComponentInterface
{
    public function __construct(
        public TransactionLog $transaction,
        public ?int $index = null,
        public bool $showDetails = true,
        public ?int $draftId = null,
    ) {}

    public function type(): string
    {
        return 'transaction_card';
    }

    public function toArray(): array
    {
        $trx = $this->transaction;

        // Resolve type key
        $typeKey = TransactionIntent::typeKeyFromName($trx->type?->name);

        // Human-readable type label (Indonesian)
        $typeLabel = match ($typeKey) {
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran',
            'transfer' => 'Transfer',
            'debt' => 'Hutang/Piutang',
            'receivable' => 'Hutang/Piutang',
            default => 'Transaksi',
        };

        $sourceWallet = $trx->sourceWallet?->name;
        $destWallet = $trx->destinationWallet?->name;
        $needsWallet = ! $trx->is_cleared && (
            $trx->sourceWallet?->group_type === 'System'
            || $trx->destinationWallet?->group_type === 'System'
        );

        $result = [
            'type' => $this->type(),
            'index' => $this->index,
            'show_details' => $this->showDetails,
            'needs_wallet' => $needsWallet,
            'transaction' => [
                'id' => $trx->id,
                'reference_number' => $trx->reference_number,
                'amount' => $trx->amount,
                'amount_formatted' => MoneyFormatter::rupiah((float) ($trx->amount ?? 0)),
                'is_cleared' => $trx->is_cleared,
                'type_key' => $typeKey,
                'type_label' => $typeLabel,
                'category' => $trx->category?->category_name,
                'type' => $trx->type?->name,
                'source_wallet' => $sourceWallet,
                'dest_wallet' => $destWallet,
                'subject' => $trx->subject,
                'notes' => $trx->notes,
                'date' => $trx->date?->toDateString(),
            ],
        ];

        // Jika ini adalah draft, tambahkan draft_id ke output
        // WebFormatter akan membaca property ini untuk menentukan ID yang dikirim ke frontend
        if ($this->draftId !== null) {
            $result['draft_id'] = $this->draftId;
            $result['is_draft'] = true;
            $result['transaction']['is_draft'] = true;
            $result['transaction']['draft_id'] = $this->draftId;
        }

        return $result;
    }
}
