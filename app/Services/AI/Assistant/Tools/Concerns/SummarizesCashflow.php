<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant\Tools\Concerns;

use App\Models\User;
use App\Support\TransactionTypeIds;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Query agregat bulanan yang dipakai get_monthly_summary dan compare_periods.
 * Hanya transaksi cleared (bukan draft) milik user yang bersangkutan.
 */
trait SummarizesCashflow
{
    /**
     * @return array{year:int,month:int,label:string,income:float,expense:float,net:float,transaction_count:int}
     */
    protected function summarizeMonth(User $user, int $year, int $month): array
    {
        $this->assertMonthInRange($year, $month);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $incomeId = TransactionTypeIds::income();
        $expenseId = TransactionTypeIds::expense();

        $row = DB::table('transaction_logs')
            ->where('user_id', $user->id)
            ->where('is_cleared', true)
            ->whereNull('deleted_at')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('type_id', [$incomeId, $expenseId])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type_id = ? THEN amount ELSE 0 END), 0) as total_income, '
                .'COALESCE(SUM(CASE WHEN type_id = ? THEN amount ELSE 0 END), 0) as total_expense, '
                .'COUNT(*) as transaction_count',
                [$incomeId, $expenseId]
            )
            ->first();

        $income = (float) ($row->total_income ?? 0);
        $expense = (float) ($row->total_expense ?? 0);

        // Draft (is_cleared=false) tidak masuk agregat, tapi jumlahnya
        // dilaporkan agar LLM tidak menyimpulkan "data kosong" padahal
        // ada transaksi yang belum dikonfirmasi pengguna.
        $draftCount = DB::table('transaction_logs')
            ->where('user_id', $user->id)
            ->where('is_cleared', false)
            ->whereNull('deleted_at')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->count();

        return [
            'year' => $year,
            'month' => $month,
            'label' => $start->translatedFormat('F Y'),
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
            'transaction_count' => (int) ($row->transaction_count ?? 0),
            'draft_count' => $draftCount,
        ];
    }

    protected function assertMonthInRange(int $year, int $month): void
    {
        if ($month < 1 || $month > 12) {
            throw ValidationException::withMessages(['month' => 'Bulan harus antara 1 dan 12.']);
        }

        $period = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $min = now()->subMonths(12)->startOfMonth();
        $max = now()->endOfMonth();

        if ($period->lt($min) || $period->gt($max)) {
            throw ValidationException::withMessages([
                'month' => 'Periode hanya boleh dalam 12 bulan terakhir dan tidak boleh di masa depan.',
            ]);
        }
    }

    /** @return array{year:int,month:int} */
    protected function parsePeriod(string $value): array
    {
        if (! preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $value, $m)) {
            throw ValidationException::withMessages(['period' => 'Format periode harus YYYY-MM.']);
        }

        return ['year' => (int) $m[1], 'month' => (int) $m[2]];
    }
}
