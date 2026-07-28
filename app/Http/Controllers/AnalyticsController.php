<?php

namespace App\Http\Controllers;

use App\Jobs\BuildNetWorthSnapshotsJob;
use App\Models\NetWorthSnapshot;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    // Transaction Type Constants mapping untuk menghindari magic numbers
    private const TYPE_INCOME = 1;
    private const TYPE_EXPENSE = 2;
    private const TYPE_DEBT = 4;
    private const TYPE_RECEIVABLE = 5;

    public function index(Request $request): Response
    {
        $userId = Auth::id();
        
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // 1. Ambil Summary Totals dalam 1 Query (Hindari memuat ribuan row)
        $totalsRaw = DB::table('transaction_logs')
            ->selectRaw('type_id, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('is_cleared', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE, self::TYPE_DEBT, self::TYPE_RECEIVABLE])
            ->groupBy('type_id')
            ->pluck('total', 'type_id');

        $totalIncome = (float) ($totalsRaw[self::TYPE_INCOME] ?? 0);
        $totalExpense = (float) ($totalsRaw[self::TYPE_EXPENSE] ?? 0);
        $totalDebt = (float) ($totalsRaw[self::TYPE_DEBT] ?? 0);
        $totalReceivable = (float) ($totalsRaw[self::TYPE_RECEIVABLE] ?? 0);

        // 2. Hitung Saldo Kumulatif Awal (Satu query agregasi tanpa meload row history)
        $runningBalance = (float) DB::table('transaction_logs')
            ->where('user_id', $userId)
            ->where('is_cleared', true)
            ->where('date', '<', $startDate)
            ->whereIn('type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE, self::TYPE_DEBT, self::TYPE_RECEIVABLE])
            ->sum(DB::raw('CASE 
                WHEN type_id IN (1, 5) THEN amount 
                WHEN type_id IN (2, 4) THEN -amount 
                ELSE 0 
            END'));

        // 3. Ambil data Harian (Grouping di level DB)
        $dailyDataRaw = DB::table('transaction_logs')
            ->selectRaw('date, type_id, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('is_cleared', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE, self::TYPE_DEBT, self::TYPE_RECEIVABLE])
            ->groupBy('date', 'type_id')
            ->get()
            ->groupBy('date');

        $dailyLabels = [];
        $dailyIncome = [];
        $dailyExpense = [];
        $cumulativeData = [];

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

            $dayTx = $dailyDataRaw->get($dateStr, collect());
            
            // Perbaikan Null-safe operator (?->)
            $dInc = (float) ($dayTx->firstWhere('type_id', self::TYPE_INCOME)?->total ?? 0);
            $dExp = (float) ($dayTx->firstWhere('type_id', self::TYPE_EXPENSE)?->total ?? 0);
            $dDebt = (float) ($dayTx->firstWhere('type_id', self::TYPE_DEBT)?->total ?? 0);
            $dRec = (float) ($dayTx->firstWhere('type_id', self::TYPE_RECEIVABLE)?->total ?? 0);

            $dailyIncome[] = $dInc;
            $dailyExpense[] = $dExp;

            $runningBalance += ($dInc + $dRec - $dExp - $dDebt);
            $cumulativeData[] = $runningBalance;

            $currentIndex++;
        }

        $cumulativeBalance = $runningBalance;

        // 4. Ringkasan per Kategori (Query tunggal dengan JOIN)
        $categoriesRaw = DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->selectRaw('categories.id, categories.category_name as name, categories.icon, transaction_logs.type_id, SUM(transaction_logs.amount) as total')
            ->where('transaction_logs.user_id', $userId)
            ->where('transaction_logs.is_cleared', true)
            ->whereBetween('transaction_logs.date', [$startDate, $endDate])
            ->whereIn('transaction_logs.type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE, self::TYPE_DEBT, self::TYPE_RECEIVABLE])
            ->groupBy('categories.id', 'categories.category_name', 'categories.icon', 'transaction_logs.type_id')
            ->orderByDesc('total')
            ->get();

        $formatCategory = fn($c) => ['id' => $c->id, 'name' => $c->name, 'icon' => $c->icon, 'total' => (float) $c->total];

        $expensesByCategory = $categoriesRaw->where('type_id', self::TYPE_EXPENSE)->map($formatCategory)->values()->toArray();
        $incomesByCategory = $categoriesRaw->where('type_id', self::TYPE_INCOME)->map($formatCategory)->values()->toArray();
        $debtsByCategory = $categoriesRaw->where('type_id', self::TYPE_DEBT)->map($formatCategory)->values()->toArray();
        $receivablesByCategory = $categoriesRaw->where('type_id', self::TYPE_RECEIVABLE)->map($formatCategory)->values()->toArray();

        // 5. Histori Keseluruhan (Agregasi di DB, output hanya tanggal yang ada transaksinya)
        $allHistoryRaw = DB::table('transaction_logs')
            ->selectRaw('date, type_id, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('is_cleared', true)
            ->whereIn('type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE, self::TYPE_DEBT, self::TYPE_RECEIVABLE])
            ->groupBy('date', 'type_id')
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('date');

        $allDailyLabels = [];
        $allDailyIncome = [];
        $allDailyExpense = [];
        $allDailyDebt = [];
        $allDailyReceivable = [];

        foreach ($allHistoryRaw as $date => $trxs) {
            $allDailyLabels[] = Carbon::parse($date)->format('d M Y');
            
            // Perbaikan Null-safe operator (?->)
            $allDailyIncome[] = (float) ($trxs->firstWhere('type_id', self::TYPE_INCOME)?->total ?? 0);
            $allDailyExpense[] = (float) ($trxs->firstWhere('type_id', self::TYPE_EXPENSE)?->total ?? 0);
            $allDailyDebt[] = (float) ($trxs->firstWhere('type_id', self::TYPE_DEBT)?->total ?? 0);
            $allDailyReceivable[] = (float) ($trxs->firstWhere('type_id', self::TYPE_RECEIVABLE)?->total ?? 0);
        }

        // 6. Snapshot Logic (Optimasi kolom fetch)
        $periodDays = iterator_count($period);
        $snapshots = NetWorthSnapshot::where('user_id', $userId)
            ->whereBetween('snapshot_date', [$startDate, $endDate])
            ->orderBy('snapshot_date', 'asc')
            ->get(['snapshot_date', 'net_worth']); 

        if ($snapshots->count() === $periodDays) {
            $cumulativeData = $snapshots->pluck('net_worth')->map(fn ($v) => (float) $v)->toArray();
            $cumulativeBalance = (float) $snapshots->last()->net_worth;
        } else {
            BuildNetWorthSnapshotsJob::dispatch($userId, $startDate, $endDate);
        }

        return Inertia::render('Analytics/Index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalDebt' => $totalDebt,
            'totalReceivable' => $totalReceivable,
            'cumulativeBalance' => $cumulativeBalance,
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