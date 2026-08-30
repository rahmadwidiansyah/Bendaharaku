<?php

namespace App\Traits;

use App\Models\LoanBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait CalculatesDebtAndReceivable
{
    public function calculateAllBalances(): array
    {
        $balances = LoanBalance::where('user_id', Auth::id())
            ->where('balance', '>', 0.001)
            ->orderByDesc('last_transaction_at')
            ->get();

        $debts = [];
        $receivables = [];
        $totalDebt = 0;
        $totalReceivable = 0;

        foreach ($balances as $balance) {
            $item = [
                'subject' => $balance->subject,
                'balance' => $balance->balance,
                'since' => $balance->opened_at,
                'age' => $balance->opened_at ? (int) Carbon::parse($balance->opened_at)->diffInDays(Carbon::now()) : 0,
                'latest_date' => $balance->last_transaction_at,
            ];

            if ($balance->loan_type === 'debt') {
                $debts[] = $item;
                $totalDebt += $balance->balance;
            } else {
                $receivables[] = $item;
                $totalReceivable += $balance->balance;
            }
        }

        return [
            'debts' => $debts,
            'receivables' => $receivables,
            'total_debt' => $totalDebt,
            'total_receivable' => $totalReceivable,
        ];
    }
}
