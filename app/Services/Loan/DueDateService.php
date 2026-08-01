<?php

namespace App\Services\Loan;

use App\Models\TransactionLog;
use Illuminate\Support\Carbon;

/**
 * DueDateService — satu sumber kebenaran untuk perhitungan tanggal jatuh tempo.
 *
 * Logika diekstrak dari DashboardController (widget Upcoming Debts) agar
 * pengingat push dan dashboard selalu konsisten.
 */
class DueDateService
{
    public function nextDueDate(TransactionLog $trx, ?Carbon $today = null): ?Carbon
    {
        $today = $today ?? Carbon::now()->startOfDay();

        if ($trx->due_date_type === 'fixed' && $trx->due_date) {
            return Carbon::parse($trx->due_date)->startOfDay();
        }

        if ($trx->due_date_type === 'monthly' && $trx->due_date_interval) {
            $day = min(31, max(1, (int) $trx->due_date_interval));
            $next = $today->copy()->setDay($day)->startOfDay();
            if ($next->isBefore($today)) {
                $next->addMonth();
            }

            return $next;
        }

        if ($trx->due_date_type === 'daily' && $trx->due_date_interval && $trx->date) {
            $start = Carbon::parse($trx->date)->startOfDay();
            $interval = (int) $trx->due_date_interval;

            if ($start->isAfter($today)) {
                return $start;
            }

            $diff = abs((int) $today->diffInDays($start));
            $cyclesPassed = (int) floor($diff / $interval);

            return $start->copy()->addDays(($cyclesPassed + 1) * $interval);
        }

        return null;
    }
}
