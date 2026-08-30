<?php

namespace App\Services\Loan;

use App\Models\LoanBalance;
use App\Models\TransactionLog;
use RuntimeException;

class LoanBalanceService
{
    private const POSITIVE_KEYS = [
        'LOAN' => 'debt',
        'RECEIVABLE' => 'receivable',
    ];

    private const NEGATIVE_KEYS = [
        'DEBT_PAYMENT' => 'debt',
        'RECEIVABLE_PAYMENT' => 'receivable',
    ];

    public function validate(TransactionLog $transaction): void
    {
        if (! $transaction->is_cleared || ! $transaction->subject) {
            return;
        }

        $key = $transaction->category()->value('system_key');
        $loanType = self::POSITIVE_KEYS[$key] ?? self::NEGATIVE_KEYS[$key] ?? null;
        if (! $loanType) {
            return;
        }

        $subject = strtoupper(trim($transaction->subject));
        if ($subject === '' || $subject === '-') {
            return;
        }

        $balance = LoanBalance::where('user_id', $transaction->user_id)
            ->where('subject', $subject)
            ->where('loan_type', $loanType)
            ->lockForUpdate()
            ->first();

        $current = (float) ($balance?->balance ?? 0);
        $delta = isset(self::POSITIVE_KEYS[$key]) ? (float) $transaction->amount : -(float) $transaction->amount;
        $next = $current + $delta;

        if ($next < -0.001) {
            throw new RuntimeException('Transaksi ditolak: pembayaran melebihi saldo '.($loanType === 'debt' ? 'hutang' : 'piutang').' '.$subject.'.');
        }

        $this->rebuild($transaction->user_id, $subject, $loanType);
    }

    public function rebuild(int $userId, string $subject, string $loanType): void
    {
        $subject = strtoupper(trim($subject));
        $balance = LoanBalance::where('user_id', $userId)
            ->where('subject', $subject)
            ->where('loan_type', $loanType)
            ->lockForUpdate()
            ->first();

        $transactions = TransactionLog::where('user_id', $userId)
            ->where('is_cleared', true)
            ->where('subject', $subject)
            ->whereHas('category', function ($query) use ($loanType) {
                $query->whereIn('system_key', $loanType === 'debt'
                    ? ['LOAN', 'DEBT_PAYMENT']
                    : ['RECEIVABLE', 'RECEIVABLE_PAYMENT']);
            })
            ->with('category:id,system_key')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $amount = 0.0;
        $openedAt = null;
        $latest = null;
        foreach ($transactions as $transaction) {
            $key = $transaction->category->system_key;
            $positive = in_array($key, ['LOAN', 'RECEIVABLE'], true);
            if ($positive && $amount <= 0.001) {
                $openedAt = $transaction->date;
            }
            $amount += $positive ? (float) $transaction->amount : -(float) $transaction->amount;
            $latest = $transaction->date;
            if ($amount < -0.001) {
                throw new RuntimeException('Data hutang/piutang tidak valid: saldo menjadi negatif untuk '.$subject.'.');
            }
            if ($amount <= 0.001) {
                $amount = 0.0;
                $openedAt = null;
            }
        }

        if ($amount <= 0.001) {
            $balance?->delete();

            return;
        }

        LoanBalance::updateOrCreate(
            ['user_id' => $userId, 'subject' => $subject, 'loan_type' => $loanType],
            ['balance' => $amount, 'opened_at' => $openedAt, 'last_transaction_at' => $latest],
        );
    }

    public function rebuildAll(int $userId): void
    {
        $pairs = TransactionLog::where('user_id', $userId)
            ->where('is_cleared', true)
            ->whereNotNull('subject')
            ->whereHas('category', fn ($query) => $query->whereIn('system_key', array_merge(array_keys(self::POSITIVE_KEYS), array_keys(self::NEGATIVE_KEYS))))
            ->with('category:id,system_key')
            ->get()
            ->map(fn ($transaction) => [
                'subject' => strtoupper(trim($transaction->subject)),
                'loan_type' => self::POSITIVE_KEYS[$transaction->category->system_key] ?? self::NEGATIVE_KEYS[$transaction->category->system_key],
            ])->unique(fn ($pair) => $pair['subject'].'|'.$pair['loan_type']);

        foreach ($pairs as $pair) {
            $this->rebuild($userId, $pair['subject'], $pair['loan_type']);
        }
    }
}
