<?php

namespace App\Http\Controllers;

use App\Support\TransactionTypeIds;

use App\Jobs\BuildNetWorthSnapshotsJob;
use App\Traits\CalculatesDebtAndReceivable;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    use CalculatesDebtAndReceivable;

    // Id tipe transaksi di-resolve dari DB by name (jangan hardcode —
    // id tabel transaction_types di-generate seeder dan bisa bergeser).

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
        // Lazy history: 'range' melewati 3 agregat full-history (bagian 5)
        // dan mengirim array kosong + openingBalance; chart mengambilnya
        // on-demand via analytics.history. Default 'full' = perilaku lama.
        $historyMode = $request->input('history', 'full');
        if (! in_array($historyMode, ['full', 'range'], true)) {
            $historyMode = 'full';
        }

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
            ->whereIn('type_id', [TransactionTypeIds::income(), TransactionTypeIds::expense()])
            ->groupBy('type_id')
            ->pluck('total', 'type_id');

        $totalIncome = (float) ($totalsRaw[TransactionTypeIds::income()] ?? 0);
        $totalExpense = (float) ($totalsRaw[TransactionTypeIds::expense()] ?? 0);

        // 2. Running balance awal (sebelum startDate)
        $runningBalance = (float) DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->where('transaction_logs.user_id', $userId)
            ->where('transaction_logs.is_cleared', true)
            ->whereNull('transaction_logs.deleted_at')
            ->where('transaction_logs.date', '<', $startDate)
            ->whereIn('transaction_logs.type_id', [TransactionTypeIds::income(), TransactionTypeIds::expense(), TransactionTypeIds::debt(), TransactionTypeIds::receivable()])
            ->sum(DB::raw($this->signedCashCase()));

        // Saldo awal sebelum rentang (untuk lazy history: chart kumulatif
        // dapat dimulai dari angka ini tanpa seluruh histori).
        $openingBalance = $runningBalance;

        // 2b. Net cash change per hari
        $dailyNetRaw = DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->selectRaw('transaction_logs.date as tx_date, SUM('.$this->signedCashCase().') as net_change')
            ->where('transaction_logs.user_id', $userId)
            ->where('transaction_logs.is_cleared', true)
            ->whereNull('transaction_logs.deleted_at')
            ->whereBetween('transaction_logs.date', [$startDate, $endDate])
            ->whereIn('transaction_logs.type_id', [TransactionTypeIds::income(), TransactionTypeIds::expense(), TransactionTypeIds::debt(), TransactionTypeIds::receivable()])
            ->groupBy('transaction_logs.date')
            ->pluck('net_change', 'tx_date');

        // 3. Daily income/expense (gross) & debt/receivable NET per hari
        $dailyDataRaw = DB::table('transaction_logs')
            ->selectRaw('date, type_id, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('is_cleared', true)
            ->whereNull('deleted_at')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('type_id', [TransactionTypeIds::income(), TransactionTypeIds::expense()])
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

            $dailyLabels[] = $dateObj->translatedFormat('d M');

            $dayTx = $dailyDataRaw->get($dateStr, collect());

            $dInc = (float) ($dayTx->firstWhere('type_id', TransactionTypeIds::income())?->total ?? 0);
            $dExp = (float) ($dayTx->firstWhere('type_id', TransactionTypeIds::expense())?->total ?? 0);

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
            ->whereIn('transaction_logs.type_id', [TransactionTypeIds::income(), TransactionTypeIds::expense(), TransactionTypeIds::debt(), TransactionTypeIds::receivable()])
            ->groupBy('categories.id', 'categories.category_name', 'categories.icon', 'transaction_logs.type_id')
            ->orderByDesc('total')
            ->get();

        $formatCategory = fn ($c) => ['id' => $c->id, 'name' => $c->name, 'icon' => $c->icon, 'total' => (float) $c->total];

        $expensesByCategory = $categoriesRaw->where('type_id', TransactionTypeIds::expense())->map($formatCategory)->values()->toArray();
        $incomesByCategory = $categoriesRaw->where('type_id', TransactionTypeIds::income())->map($formatCategory)->values()->toArray();
        $debtsByCategory = $categoriesRaw->where('type_id', TransactionTypeIds::debt())->map($formatCategory)->values()->toArray();
        $receivablesByCategory = $categoriesRaw->where('type_id', TransactionTypeIds::receivable())->map($formatCategory)->values()->toArray();

        // 5. Full history — Income/Expense gross, Debt/Receivable NET per hari.
        // Dilewati bila history=range (lazy): klien mengambil on-demand via
        // analytics.history. Bentuk dan urutan array identik di kedua jalur.
        $fullHistory = $historyMode === 'full'
            ? $this->fullHistoryArrays($userId)
            : ['labels' => [], 'dates' => [], 'income' => [], 'expense' => [], 'debt' => [], 'receivable' => []];
        $allLabels = $fullHistory['labels'];
        $allDates = $fullHistory['dates'];
        $allIncome = $fullHistory['income'];
        $allExpense = $fullHistory['expense'];
        $allDebt = $fullHistory['debt'];
        $allReceivable = $fullHistory['receivable'];

        // 6. Rebuild snapshot di background untuk dipakai kunjungan berikutnya
        BuildNetWorthSnapshotsJob::dispatch($userId, $startDate, $endDate);

        // Multi-UI: pilih komponen halaman sesuai preferensi user.
        // Pengumpulan data & payload props identik untuk kedua tampilan.
        $view = $request->user()->ui_theme === 'm3' ? 'Material/Analytics' : 'Analytics/Index';

        return Inertia::render($view, [
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
            'historyMode' => $historyMode,
            'openingBalance' => $openingBalance,
            'allDailyLabels' => $allLabels,
            'allDailyDates' => $allDates,
            'allDailyIncome' => $allIncome,
            'allDailyExpense' => $allExpense,
            'allDailyDebt' => $allDebt,
            'allDailyReceivable' => $allReceivable,
        ]);
    }

    /**
     * JSON on-demand untuk full history (dipakai lazy-loading chart).
     * GET /analytics/history — array identik dengan props allDaily* mode full.
     */
    public function history(): JsonResponse
    {
        $arrays = $this->fullHistoryArrays(Auth::id());

        return response()->json([
            'labels' => $arrays['labels'],
            'dates' => $arrays['dates'],
            'income' => $arrays['income'],
            'expense' => $arrays['expense'],
            'debt' => $arrays['debt'],
            'receivable' => $arrays['receivable'],
        ]);
    }

    /**
     * @return array{labels: list<string>, dates: list<string>, income: list<float>, expense: list<float>, debt: list<float>, receivable: list<float>}
     */
    private function fullHistoryArrays(int $userId): array
    {
        $labels = [];
        $dates = [];
        $income = [];
        $expense = [];
        $debt = [];
        $receivable = [];

        // Income & Expense per day (gross by type_id)
        $allHistoryRaw = DB::table('transaction_logs')
            ->selectRaw('date, type_id, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('is_cleared', true)
            ->whereNull('deleted_at')
            ->whereIn('type_id', [TransactionTypeIds::income(), TransactionTypeIds::expense()])
            ->groupBy('date', 'type_id')
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('date');

        // Debt & Receivable NET per day
        $allDebtRaw = DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->selectRaw('transaction_logs.date, SUM('.$this->debtNetCase().') as net')
            ->where('transaction_logs.user_id', $userId)
            ->where('is_cleared', true)
            ->whereNull('transaction_logs.deleted_at')
            ->whereIn('categories.system_key', ['LOAN', 'DEBT_PAYMENT'])
            ->groupBy('transaction_logs.date')
            ->orderBy('transaction_logs.date', 'asc')
            ->pluck('net', 'date');

        $allReceivableRaw = DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->selectRaw('transaction_logs.date, SUM('.$this->receivableNetCase().') as net')
            ->where('transaction_logs.user_id', $userId)
            ->where('is_cleared', true)
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
            $labels[] = Carbon::parse($date)->translatedFormat('d M Y');
            $dates[] = Carbon::parse($date)->format('Y-m-d');

            $dayTx = $allHistoryRaw->get($date, collect());
            $income[] = (float) ($dayTx->firstWhere('type_id', TransactionTypeIds::income())?->total ?? 0);
            $expense[] = (float) ($dayTx->firstWhere('type_id', TransactionTypeIds::expense())?->total ?? 0);
            $debt[] = (float) ($allDebtRaw[$date] ?? 0);
            $receivable[] = (float) ($allReceivableRaw[$date] ?? 0);
        }

        return compact('labels', 'dates', 'income', 'expense', 'debt', 'receivable');
    }
}
