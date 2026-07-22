<?php

namespace App\Http\Controllers;

use App\Jobs\BuildNetWorthSnapshotsJob;
use App\Models\NetWorthSnapshot;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // DEFAULT START: Awal bulan
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));

        // DEFAULT END: Ganti ke Today
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $transactions = $user->transactionLogs()
            ->with(['type', 'category'])
            ->where('is_cleared', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Use safe collection filters (avoid dot-notation) and only cleared transactions
        $totalIncome = (float) $transactions->filter(fn ($t) => $t->type?->name === 'Income')->sum('amount');
        $totalExpense = (float) $transactions->filter(fn ($t) => $t->type?->name === 'Expense')->sum('amount');
        $totalDebt = (float) $transactions->filter(fn ($t) => $t->type?->name === 'Debt')->sum('amount');
        $totalReceivable = (float) $transactions->filter(fn ($t) => $t->type?->name === 'Receivable')->sum('amount');

        // Saldo Kumulatif - hitung semua transaksi sebelum startDate (cleared only)
        $initialTransactions = $user->transactionLogs()
            ->with('type')
            ->where('is_cleared', true)
            ->where('date', '<', $startDate)
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $runningBalance = 0;
        $runningBalance += (float) $initialTransactions->filter(fn ($t) => $t->type?->name === 'Income')->sum('amount');
        $runningBalance += (float) $initialTransactions->filter(fn ($t) => $t->type?->name === 'Receivable')->sum('amount');
        $runningBalance -= (float) $initialTransactions->filter(fn ($t) => $t->type?->name === 'Expense')->sum('amount');
        $runningBalance -= (float) $initialTransactions->filter(fn ($t) => $t->type?->name === 'Debt')->sum('amount');

        $dailyLabels = [];
        $dailyIncome = [];
        $dailyExpense = [];
        $cumulativeData = [];

        // Group by date string to avoid Carbon object issues
        $txByDate = $transactions->groupBy(fn ($t) => $t->date->toDateString());
        $period = CarbonPeriod::create($startDate, $endDate);

        $todayStr = Carbon::now()->format('Y-m-d');
        $todayIndex = -1;
        $currentIndex = 0;

        foreach ($period as $dateObj) {
            $dateStr = $dateObj->format('Y-m-d');

            if ($dateStr === $todayStr) {
                $todayIndex = $currentIndex;
            }

            $dailyLabels[] = $dateObj->format('d M');

            $dayTx = $txByDate->get($dateStr, collect());
            $dInc = (float) $dayTx->filter(fn ($t) => $t->type?->name === 'Income')->sum('amount');
            $dExp = (float) $dayTx->filter(fn ($t) => $t->type?->name === 'Expense')->sum('amount');
            $dDebt = (float) $dayTx->filter(fn ($t) => $t->type?->name === 'Debt')->sum('amount');
            $dReceivable = (float) $dayTx->filter(fn ($t) => $t->type?->name === 'Receivable')->sum('amount');

            $dailyIncome[] = $dInc;
            $dailyExpense[] = $dExp;

            // Kumulatif: +income +receivable -expense -debt
            $runningBalance += ($dInc + $dReceivable - $dExp - $dDebt);
            $cumulativeData[] = (float) $runningBalance;

            $currentIndex++;
        }

        $cumulativeBalance = $runningBalance;

        $expensesByCategory = $transactions->where('type.name', 'Expense')
            ->groupBy('category_id')
            ->map(function ($rows) {
                $category = $rows->first()->category;

                return [
                    'id' => $category->id,
                    'name' => $category->category_name,
                    'icon' => $category->icon,
                    'total' => (float) $rows->sum('amount'),
                ];
            })->sortByDesc('total')->values();

        $incomesByCategory = $transactions->where('type.name', 'Income')
            ->groupBy('category_id')
            ->map(function ($rows) {
                $category = $rows->first()->category;

                return [
                    'id' => $category->id,
                    'name' => $category->category_name,
                    'icon' => $category->icon,
                    'total' => (float) $rows->sum('amount'),
                ];
            })->sortByDesc('total')->values();

        $debtsByCategory = $transactions->where('type.name', 'Debt')
            ->groupBy('category_id')
            ->map(function ($rows) {
                $category = $rows->first()->category;

                return [
                    'id' => $category->id,
                    'name' => $category->category_name,
                    'icon' => $category->icon,
                    'total' => (float) $rows->sum('amount'),
                ];
            })->sortByDesc('total')->values();

        $receivablesByCategory = $transactions->where('type.name', 'Receivable')
            ->groupBy('category_id')
            ->map(function ($rows) {
                $category = $rows->first()->category;

                return [
                    'id' => $category->id,
                    'name' => $category->category_name,
                    'icon' => $category->icon,
                    'total' => (float) $rows->sum('amount'),
                ];
            })->sortByDesc('total')->values();

        $allTransactions = $user->transactionLogs()->where('is_cleared', true)->orderBy('date', 'asc')->orderBy('created_at', 'asc')->get();
        $allKasGrouped = $allTransactions->groupBy(fn ($t) => $t->date?->toDateString() ?? (string) $t->date);

        $allDailyLabels = [];
        $allDailyIncome = [];
        $allDailyExpense = [];
        $allDailyDebt = [];
        $allDailyReceivable = [];

        foreach ($allKasGrouped as $date => $trxs) {
            $allDailyLabels[] = Carbon::parse($date)->format('d M Y');
            $allDailyIncome[] = (float) $trxs->filter(fn ($t) => $t->type?->name === 'Income')->sum('amount');
            $allDailyExpense[] = (float) $trxs->filter(fn ($t) => $t->type?->name === 'Expense')->sum('amount');
            $allDailyDebt[] = (float) $trxs->filter(fn ($t) => $t->type?->name === 'Debt')->sum('amount');
            $allDailyReceivable[] = (float) $trxs->filter(fn ($t) => $t->type?->name === 'Receivable')->sum('amount');
        }

        // If snapshots exist for full period, prefer snapshots for performance/accuracy
        $periodDays = iterator_count($period);
        $snapshots = NetWorthSnapshot::where('user_id', $user->id)
            ->whereBetween('snapshot_date', [$startDate, $endDate])
            ->orderBy('snapshot_date', 'asc')
            ->get();

        if ($snapshots->count() === $periodDays) {
            $cumulativeData = $snapshots->pluck('net_worth')->map(fn ($v) => (float) $v)->toArray();
            $cumulativeBalance = (float) $snapshots->last()->net_worth;
        } else {
            // dispatch job to build snapshots in background (non-blocking)
            BuildNetWorthSnapshotsJob::dispatch($user->id, $startDate, $endDate);
        }

        return Inertia::render('Analytics/Index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalDebt' => $totalDebt,
            'totalReceivable' => $totalReceivable,
            'cumulativeBalance' => (float) $cumulativeBalance,
            'expensesByCategory' => $expensesByCategory,
            'incomesByCategory' => $incomesByCategory,
            'debtsByCategory' => $debtsByCategory,
            'receivablesByCategory' => $receivablesByCategory,
            'dailyLabels' => $dailyLabels,
            'dailyIncome' => $dailyIncome,
            'dailyExpense' => $dailyExpense,
            'cumulativeData' => $cumulativeData,
            'todayIndex' => $todayIndex,
            'allDailyLabels' => $allDailyLabels,
            'allDailyIncome' => $allDailyIncome,
            'allDailyExpense' => $allDailyExpense,
            'allDailyDebt' => $allDailyDebt,
            'allDailyReceivable' => $allDailyReceivable,
        ]);
    }
}
