<?php

namespace App\Services\Loan;

use App\Models\TransactionLog;
use App\Models\User;
use Carbon\Carbon;

class ActiveLoanCycleService
{
    public function calculateForUser(User $user, ?Carbon $today = null): array
    {
        $today = $today ?? Carbon::today();
        $transactions = $user->transactionLogs()
            ->where('is_cleared', true)
            ->whereNotNull('subject')
            ->whereHas('category', function ($query) {
                $query->whereIn('system_key', [
                    'LOAN', 'DEBT_PAYMENT', 'RECEIVABLE', 'RECEIVABLE_PAYMENT',
                ]);
            })
            ->with('category:id,system_key')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $cycles = [];
        foreach ($transactions as $transaction) {
            $subject = strtoupper(trim($transaction->subject));
            $key = $subject.'|'.($this->isDebt($transaction) ? 'debt' : 'receivable');
            $type = str_contains($key, '|debt') ? 'debt' : 'receivable';
            $amount = (float) $transaction->amount;
            $isOpening = in_array($transaction->category->system_key, ['LOAN', 'RECEIVABLE'], true);

            if (! isset($cycles[$key])) {
                $cycles[$key] = $this->emptyCycle($subject, $type);
            }

            if ($isOpening) {
                if ($cycles[$key]['balance'] <= 0) {
                    $cycles[$key]['since'] = $transaction->date?->toDateString();
                    $cycles[$key]['opening_transaction'] = $transaction;
                }
                $cycles[$key]['balance'] += $amount;
            } else {
                $cycles[$key]['balance'] -= $amount;
                if ($cycles[$key]['balance'] <= 0) {
                    $cycles[$key] = $this->emptyCycle($subject, $type);
                }
            }

            if ($cycles[$key]['balance'] > 0) {
                $cycles[$key]['latest_date'] = $transaction->date?->toDateString();
            }
        }

        $result = ['debts' => [], 'receivables' => [], 'total_debt' => 0, 'total_receivable' => 0];
        foreach ($cycles as $cycle) {
            if ($cycle['balance'] <= 0 || ! $cycle['since']) {
                continue;
            }
            $opening = $cycle['opening_transaction'];
            $dueDate = $opening ? app(DueDateService::class)->nextDueDate($opening, $today) : null;
            $cycle['age'] = Carbon::parse($cycle['since'])->diffInDays($today);
            $cycle['due_date'] = $dueDate;
            $result[$cycle['type'] === 'debt' ? 'debts' : 'receivables'][] = $cycle;
            $result[$cycle['type'] === 'debt' ? 'total_debt' : 'total_receivable'] += $cycle['balance'];
        }

        usort($result['debts'], fn ($a, $b) => $b['latest_date'] <=> $a['latest_date']);
        usort($result['receivables'], fn ($a, $b) => $b['latest_date'] <=> $a['latest_date']);

        return $result;
    }

    private function emptyCycle(string $subject, string $type): array
    {
        return [
            'subject' => $subject,
            'type' => $type,
            'balance' => 0,
            'since' => null,
            'age' => 0,
            'latest_date' => null,
            'opening_transaction' => null,
            'due_date' => null,
        ];
    }

    private function isDebt(TransactionLog $transaction): bool
    {
        return in_array($transaction->category->system_key, ['LOAN', 'DEBT_PAYMENT'], true);
    }
}
