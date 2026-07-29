<?php

namespace App\Http\Controllers;

use App\Jobs\BuildNetWorthSnapshotsJob;
use App\Traits\CalculatesDebtAndReceivable;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    use CalculatesDebtAndReceivable;

    private const TYPE_INCOME = 1;
    private const TYPE_EXPENSE = 2;
    private const TYPE_DEBT = 4;
    private const TYPE_RECEIVABLE = 5;

    /**
     * Arah kas REAL (masuk = +, keluar = -) untuk cumulative balance chart.
     */
    private function signedCashCase(): string
    {
        return "
            CASE
                WHEN transaction_logs.type_id = 1 THEN transaction_logs.amount
                WHEN transaction_logs.type_id = 2 THEN -transaction_logs.amount
                WHEN categories.system_key = 'LOAN' THEN transaction_logs.amount
                WHEN categories.system_key = 'DEBT_PAYMENT' THEN -transaction_logs.amount
                WHEN categories.system_key = 'RECEIVABLE_PAYMENT' THEN transaction_logs.amount
                WHEN categories.system_key = 'RECEIVABLE' THEN -transaction_logs.amount
                ELSE 0
            END
        ";
    }

    /**
     * Net perubahan outstanding hutang: LOAN (+), DEBT_PAYMENT (-).
     */
    private function debtNetCase(): string
    {
        return "
            CASE
                WHEN categories.system_key = 'LOAN' THEN transaction_logs.amount
                WHEN categories.system_key = 'DEBT_PAYMENT' THEN -transaction_logs.amount
                ELSE 0
            END
        ";
    }

    /**
     * Net perubahan outstanding piutang: RECEIVABLE (+), RECEIVABLE_PAYMENT (-).
     */
    private function receivableNetCase(): string
    {
        return "
            CASE
                WHEN categories.system_key = 'RECEIVABLE' THEN transaction_logs.amount
                WHEN categories.system_key = 'RECEIVABLE_PAYMENT' THEN -transaction_logs.amount
                ELSE 0
            END
        ";
    }

    public function index(Request $request): Response
    {
        $userId = Auth::id();

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // Get consistent, subject-based net balances for summary cards
        $allBalances = $this->calculateAllBalances();
        $totalDebt = $allBalances['total_debt'];
        $totalReceivable = $allBalances['total_receivable'];

        // 1. Summary Totals — Income/Expense gross by type_id for the period
        $totalsRaw = DB::table('transaction_logs')
            ->selectRaw('type_id, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('is_cleared', true)
            ->whereNull('deleted_at')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE])
            ->groupBy('type_id')
            ->pluck('total', 'type_id');

        $totalIncome = (float) ($totalsRaw[self::TYPE_INCOME] ?? 0);
        $totalExpense = (float) ($totalsRaw[self::TYPE_EXPENSE] ?? 0);

        // 2. Running balance awal (sebelum startDate)
        $runningBalance = (float) DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->where('transaction_logs.user_id', $userId)
            ->where('transaction_logs.is_cleared', true)
            ->whereNull('transaction_logs.deleted_at')
            ->where('transaction_logs.date', '<', $startDate)
            ->whereIn('transaction_logs.type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE, self::TYPE_DEBT, self::TYPE_RECEIVABLE])
            ->sum(DB::raw($this->signedCashCase()));

        // 2b. Net cash change per hari
        $dailyNetRaw = DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->selectRaw('transaction_logs.date as tx_date, SUM('.$this->signedCashCase().') as net_change')
            ->where('transaction_logs.user_id', $userId)
            ->where('transaction_logs.is_cleared', true)
            ->whereNull('transaction_logs.deleted_at')
            ->whereBetween('transaction_logs.date', [$startDate, $endDate])
            ->whereIn('transaction_logs.type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE, self::TYPE_DEBT, self::TYPE_RECEIVABLE])
            ->groupBy('transaction_logs.date')
            ->pluck('net_change', 'tx_date');

        // 3. Daily income/expense (gross) & debt/receivable NET per hari
        $dailyDataRaw = DB::table('transaction_logs')
            ->selectRaw('date, type_id, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('is_cleared', true)
            ->whereNull('deleted_at')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE])
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

            $dInc = (float) ($dayTx->firstWhere('type_id', self::TYPE_INCOME)?->total ?? 0);
            $dExp = (float) ($dayTx->firstWhere('type_id', self::TYPE_EXPENSE)?->total ?? 0);

            $dailyIncome[] = $dInc;
            $dailyExpense[] = $dExp;

            $runningBalance += (float) ($dailyNetRaw[$dateStr] ?? 0);
            $cumulativeData[] = $runningBalance;

            $currentIndex++;
        }

        $cumulativeBalance = $runningBalance;

        // 4. Kategori breakdown — Income/Expense gross, Debt/Receivable per kategori
        $categoriesRaw = DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->selectRaw('categories.id, categories.category_name as name, categories.icon, transaction_logs.type_id, SUM(transaction_logs.amount) as total')
            ->where('transaction_logs.user_id', $userId)
            ->where('transaction_logs.is_cleared', true)
            ->whereNull('transaction_logs.deleted_at')
            ->whereBetween('transaction_logs.date', [$startDate, $endDate])
            ->whereIn('transaction_logs.type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE, self::TYPE_DEBT, self::TYPE_RECEIVABLE])
            ->groupBy('categories.id', 'categories.category_name', 'categories.icon', 'transaction_logs.type_id')
            ->orderByDesc('total')
            ->get();

        $formatCategory = fn ($c) => ['id' => $c->id, 'name' => $c->name, 'icon' => $c->icon, 'total' => (float) $c->total];

        $expensesByCategory = $categoriesRaw->where('type_id', self::TYPE_EXPENSE)->map($formatCategory)->values()->toArray();
        $incomesByCategory = $categoriesRaw->where('type_id', self::TYPE_INCOME)->map($formatCategory)->values()->toArray();
        $debtsByCategory = $categoriesRaw->where('type_id', self::TYPE_DEBT)->map($formatCategory)->values()->toArray();
        $receivablesByCategory = $categoriesRaw->where('type_id', self::TYPE_RECEIVABLE)->map($formatCategory)->values()->toArray();

        // 5. Full history — Income/Expense gross, Debt/Receivable NET per hari
        $allLabels = [];
        $allDates = [];
        $allIncome = [];
        $allExpense = [];
        $allDebt = [];
        $allReceivable = [];

        // Income & Expense per day (gross by type_id)
        $allHistoryRaw = DB::table('transaction_logs')
            ->selectRaw('date, type_id, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('is_cleared', true)
            ->whereNull('deleted_at')
            ->whereIn('type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE])
            ->groupBy('date', 'type_id')
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('date');

        // Debt & Receivable NET per day
        $allDebtRaw = DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->selectRaw('transaction_logs.date, SUM('.$this->debtNetCase().') as net')
            ->where('transaction_logs.user_id', $userId)
            ->where('transaction_logs.is_cleared', true)
            ->whereNull('transaction_logs.deleted_at')
            ->whereIn('categories.system_key', ['LOAN', 'DEBT_PAYMENT'])
            ->groupBy('transaction_logs.date')
            ->orderBy('transaction_logs.date', 'asc')
            ->pluck('net', 'date');

        $allReceivableRaw = DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->selectRaw('transaction_logs.date, SUM('.$this->receivableNetCase().') as net')
            ->where('transaction_logs.user_id', $userId)
            ->where('transaction_logs.is_cleared', true)
            ->whereNull('transaction_logs.deleted_at')
            ->whereIn('categories.system_key', ['RECEIVABLE', 'RECEIVABLE_PAYMENT'])
            ->groupBy('transaction_logs.date')
            ->orderBy('transaction_logs.date', 'asc')
            ->pluck('net', 'date');

        // Gabung semua tanggal yang muncul di salah satu query
        $allDateKeys = collect(array_keys($allHistoryRaw->toArray()))
            ->merge(array_keys($allDebtRaw->toArray()))
            ->merge(array_keys($allReceivableRaw->toArray()))
            ->unique()
            ->sort()
            ->values();

        foreach ($allDateKeys as $date) {
            $allLabels[] = Carbon::parse($date)->format('d M Y');
            $allDates[] = Carbon::parse($date)->format('Y-m-d');

            $dayTx = $allHistoryRaw->get($date, collect());
            $allIncome[] = (float) ($dayTx->firstWhere('type_id', self::TYPE_INCOME)?->total ?? 0);
            $allExpense[] = (float) ($dayTx->firstWhere('type_id', self::TYPE_EXPENSE)?->total ?? 0);
            $allDebt[] = (float) ($allDebtRaw[$date] ?? 0);
            $allReceivable[] = (float) ($allReceivableRaw[$date] ?? 0);
        }

        // 6. Rebuild snapshot di background untuk dipakai kunjungan berikutnya
        BuildNetWorthSnapshotsJob::dispatch($userId, $startDate, $endDate);

        return Inertia::render('Analytics/Index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalDebt' => max(0, $totalDebt),
            'totalReceivable' => max(0, $totalReceivable),
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
            'allDailyLabels' => $allLabels,
            'allDailyDates' => $allDates,
            'allDailyIncome' => $allIncome,
            'allDailyExpense' => $allExpense,
            'allDailyDebt' => $allDebt,
            'allDailyReceivable' => $allReceivable,
        ]);
    }
}
