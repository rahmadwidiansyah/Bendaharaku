<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

    // DEFAULT START: Awal bulan
    $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
    
    // DEFAULT END: Ganti ke Today (bukan endOfMonth) agar grafik tidak bablas ke masa depan
    $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        // Tarik transaksi HANYA MILIK USER SAAT INI (Aman 100%)
        $transactions = $user->transactionLogs()
            ->with(['type', 'category'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        $totalIncome = $transactions->where('type.name', 'Income')->sum('amount');
        $totalExpense = $transactions->where('type.name', 'Expense')->sum('amount');

        // Saldo Kumulatif
        $initialIncome = $user->transactionLogs()->whereHas('type', fn($q) => $q->where('name', 'Income'))->where('date', '<', $startDate)->sum('amount');
        $initialExpense = $user->transactionLogs()->whereHas('type', fn($q) => $q->where('name', 'Expense'))->where('date', '<', $startDate)->sum('amount');
        $runningBalance = $initialIncome - $initialExpense;

        $dailyLabels = [];
        $dailyIncome = [];
        $dailyExpense = [];
        $cumulativeData = [];

        $txByDate = $transactions->groupBy('date');
        $period = CarbonPeriod::create($startDate, $endDate);

        // --- DETEKSI TANGGAL HARI INI ---
        $todayStr = Carbon::now()->format('Y-m-d');
        $todayIndex = -1;
        $currentIndex = 0;

        foreach ($period as $dateObj) {
            $dateStr = $dateObj->format('Y-m-d');

            // Tandai index kalau tanggalnya cocok sama hari ini
            if ($dateStr === $todayStr) {
                $todayIndex = $currentIndex;
            }

            $dailyLabels[] = $dateObj->format('d M');

            $dayTx = $txByDate->get($dateStr, collect());
            $dInc = $dayTx->where('type.name', 'Income')->sum('amount');
            $dExp = $dayTx->where('type.name', 'Expense')->sum('amount');

            $dailyIncome[] = $dInc;
            $dailyExpense[] = $dExp;

            $runningBalance += ($dInc - $dExp);
            $cumulativeData[] = $runningBalance;

            $currentIndex++;
        }

        $cumulativeBalance = $runningBalance;

        // GANTI DENGAN KODE BARU INI:
        $expensesByCategory = $transactions->where('type.name', 'Expense')
            ->groupBy('category_id')
            ->map(function ($rows) {
                $category = $rows->first()->category;
                return [
                    'id' => $category->id,
                    'name' => $category->category_name,
                    'icon' => $category->icon,
                    'total' => $rows->sum('amount')
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
                    'total' => $rows->sum('amount')
                ];
            })->sortByDesc('total')->values();
        return view('analytics.index', compact(
            'startDate',
            'endDate',
            'totalIncome',
            'totalExpense',
            'cumulativeBalance',
            'expensesByCategory',
            'incomesByCategory',
            'dailyLabels',
            'dailyIncome',
            'dailyExpense',
            'cumulativeData',
            'todayIndex' // Passing index hari ini ke view
        ));
    }
}
