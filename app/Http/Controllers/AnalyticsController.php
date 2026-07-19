<?php

namespace App\Http\Controllers;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        $totalIncome = (float) $transactions->where('type.name', 'Income')->sum('amount');
        $totalExpense = (float) $transactions->where('type.name', 'Expense')->sum('amount');
        $totalDebt = (float) $transactions->where('type.name', 'Debt')->sum('amount');
        $totalReceivable = (float) $transactions->where('type.name', 'Receivable')->sum('amount');

        // Saldo Kumulatif - hitung semua transaksi sebelum startDate
        $initialTransactions = $user->transactionLogs()
            ->with('type')
            ->where('date', '<', $startDate)
            ->get();
        
        $runningBalance = 0;
        $runningBalance += (float) $initialTransactions->where('type.name', 'Income')->sum('amount');
        $runningBalance += (float) $initialTransactions->where('type.name', 'Receivable')->sum('amount');
        $runningBalance -= (float) $initialTransactions->where('type.name', 'Expense')->sum('amount');
        $runningBalance -= (float) $initialTransactions->where('type.name', 'Debt')->sum('amount');

        $dailyLabels = [];
        $dailyIncome = [];
        $dailyExpense = [];
        $cumulativeData = [];

        $txByDate = $transactions->groupBy('date');
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
            $dInc = (float) $dayTx->where('type.name', 'Income')->sum('amount');
            $dExp = (float) $dayTx->where('type.name', 'Expense')->sum('amount');
            $dDebt = (float) $dayTx->where('type.name', 'Debt')->sum('amount');
            $dReceivable = (float) $dayTx->where('type.name', 'Receivable')->sum('amount');

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
                    'total' => (float) $rows->sum('amount')
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
                    'total' => (float) $rows->sum('amount')
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
                    'total' => (float) $rows->sum('amount')
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
                    'total' => (float) $rows->sum('amount')
                ];
            })->sortByDesc('total')->values();
        
        $allTransactions = $user->transactionLogs()->orderBy('date', 'asc')->get(); 
        $allKasGrouped = $allTransactions->groupBy('date');

        $allDailyLabels = [];
        $allDailyIncome = [];
        $allDailyExpense = [];
        $allDailyDebt = [];
        $allDailyReceivable = [];

        foreach ($allKasGrouped as $date => $trxs) {
            $allDailyLabels[] = \Carbon\Carbon::parse($date)->format('d M Y');
            $allDailyIncome[] = (float) $trxs->where('type.name', 'Income')->sum('amount');
            $allDailyExpense[] = (float) $trxs->where('type.name', 'Expense')->sum('amount');
            $allDailyDebt[] = (float) $trxs->where('type.name', 'Debt')->sum('amount');
            $allDailyReceivable[] = (float) $trxs->where('type.name', 'Receivable')->sum('amount');
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
            'allDailyReceivable' => $allDailyReceivable
        ]);
    }
}