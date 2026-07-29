<?php

namespace App\Traits;

use App\Models\TransactionLog;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

trait CalculatesDebtAndReceivable
{
    /**
     * Calculate debt and receivable balances from transaction logs for the authenticated user.
     *
     * This method provides a single, consistent source of truth for calculating
     * outstanding debts and receivables based on a subject's transaction history.
     * It processes transactions chronologically, resets loan durations when a
     * balance is cleared, and handles overpayments correctly.
     *
     * @return array An associative array with 'debts' and 'receivables' keys.
     *               Each contains an array of subjects with their balance,
     *               since date, age, and latest transaction date.
     *               Also includes 'total_debt' and 'total_receivable' keys.
     */
    public function calculateAllBalances(): array
    {
        $userId = Auth::id();
        $transactions = TransactionLog::where('user_id', $userId)
            ->where('is_cleared', true)
            ->whereNotNull('subject')
            ->where('date', '>=', now()->subYears(3))
            ->whereHas('category', function ($query) {
                $query->whereIn('system_key', [
                    'LOAN', 'DEBT_PAYMENT', 'RECEIVABLE', 'RECEIVABLE_PAYMENT'
                ]);
            })
            ->with('category:id,system_key')
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $subjects = [];

        foreach ($transactions as $transaction) {
            $subject = strtoupper(trim($transaction->subject));
            $systemKey = $transaction->category->system_key;
            $amount = $transaction->amount;

            if (!isset($subjects[$subject])) {
                $subjects[$subject] = [
                    'debt' => 0,
                    'receivable' => 0,
                    'debt_since' => null,
                    'receivable_since' => null,
                    'latest_debt_date' => null,
                    'latest_receivable_date' => null,
                ];
            }

            $isDebt = in_array($systemKey, ['LOAN', 'DEBT_PAYMENT']);
            $isReceivable = in_array($systemKey, ['RECEIVABLE', 'RECEIVABLE_PAYMENT']);

            if ($isDebt) {
                $currentBalance = $subjects[$subject]['debt'];
                $isNewLoanCycle = ($currentBalance <= 0);

                if ($systemKey === 'LOAN') {
                    $subjects[$subject]['debt'] += $amount;
                    if ($isNewLoanCycle) {
                        $subjects[$subject]['debt_since'] = $transaction->date;
                    }
                } else { // DEBT_PAYMENT
                    $subjects[$subject]['debt'] -= $amount;
                }

                $subjects[$subject]['latest_debt_date'] = $transaction->date;

                if ($subjects[$subject]['debt'] <= 0) {
                     // Reset if paid off, but keep the latest date for sorting
                    if ($systemKey === 'DEBT_PAYMENT') {
                        $subjects[$subject]['debt_since'] = null;
                    }
                }

            } elseif ($isReceivable) {
                $currentBalance = $subjects[$subject]['receivable'];
                $isNewReceivableCycle = ($currentBalance <= 0);

                if ($systemKey === 'RECEIVABLE') {
                    $subjects[$subject]['receivable'] += $amount;
                    if ($isNewReceivableCycle) {
                        $subjects[$subject]['receivable_since'] = $transaction->date;
                    }
                } else { // RECEIVABLE_PAYMENT
                    $subjects[$subject]['receivable'] -= $amount;
                }

                 $subjects[$subject]['latest_receivable_date'] = $transaction->date;

                if ($subjects[$subject]['receivable'] <= 0) {
                    if ($systemKey === 'RECEIVABLE_PAYMENT') {
                       $subjects[$subject]['receivable_since'] = null;
                    }
                }
            }
        }

        $debts = [];
        $receivables = [];
        $totalDebt = 0;
        $totalReceivable = 0;

        foreach ($subjects as $name => $data) {
            if ($data['debt'] > 0.001) { // Use a small epsilon for float comparison
                $since = Carbon::parse($data['debt_since']);
                $debts[] = [
                    'subject' => $name,
                    'balance' => $data['debt'],
                    'since' => $data['debt_since'],
                    'age' => (int) $since->diffInDays(Carbon::now()),
                    'latest_date' => $data['latest_debt_date'],
                ];
                $totalDebt += $data['debt'];
            }
            if ($data['receivable'] > 0.001) {
                $since = Carbon::parse($data['receivable_since']);
                $receivables[] = [
                    'subject' => $name,
                    'balance' => $data['receivable'],
                    'since' => $data['receivable_since'],
                    'age' => (int) $since->diffInDays(Carbon::now()),
                    'latest_date' => $data['latest_receivable_date'],
                ];
                $totalReceivable += $data['receivable'];
            }
        }
        
        // Sort by latest transaction date descending
        usort($debts, fn($a, $b) => $b['latest_date'] <=> $a['latest_date']);
        usort($receivables, fn($a, $b) => $b['latest_date'] <=> $a['latest_date']);

        return [
            'debts' => $debts,
            'receivables' => $receivables,
            'total_debt' => $totalDebt,
            'total_receivable' => $totalReceivable,
        ];
    }
}
