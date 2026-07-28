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

    /**
     * SQL CASE untuk menentukan arah kas REAL (masuk = +, keluar = -).
     *
     * PENTING: type_id=4 (Debt) dan type_id=5 (Receivable) masing-masing berisi
     * DUA kategori dengan arah kas berlawanan:
     * - 'Dapat Hutangan'        -> uang MASUK  (+)
     * - 'Bayar Cicilan Hutang'  -> uang KELUAR (-)
     * - 'Terima Bayar Piutang'  -> uang MASUK  (+)
     * - 'Ngasih Piutang'        -> uang KELUAR (-)
     * Jadi arah TIDAK BOLEH ditentukan cuma dari type_id, harus join ke categories.
     */
    private function signedCashCase(): string
    {
        return "
            CASE
                WHEN transaction_logs.type_id = 1 THEN transaction_logs.amount
                WHEN transaction_logs.type_id = 2 THEN -transaction_logs.amount
                WHEN categories.category_name = 'Dapat Hutangan' THEN transaction_logs.amount
                WHEN categories.category_name = 'Bayar Cicilan Hutang' THEN -transaction_logs.amount
                WHEN categories.category_name = 'Terima Bayar Piutang' THEN transaction_logs.amount
                WHEN categories.category_name = 'Ngasih Piutang' THEN -transaction_logs.amount
                ELSE 0
            END
        ";
    }

    public function index(Request $request): Response
    {
        $userId = Auth::id();

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // 1. Ambil Summary Totals dalam 1 Query (Hindari memuat ribuan row)
        // NOTE: ini tetap gross sum per type_id (dipakai buat card Income/Expense &
        // breakdown kategori di doughnut), BUKAN dipakai untuk saldo real — aman.
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

        // 2. Saldo Kumulatif Awal — FIX: sign ditentukan per KATEGORI (join),
        // bukan per type_id, supaya arah kas hutang/piutang benar.
        $runningBalance = (float) DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->where('transaction_logs.user_id', $userId)
            ->where('transaction_logs.is_cleared', true)
            ->where('transaction_logs.date', '<', $startDate)
            ->whereIn('transaction_logs.type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE, self::TYPE_DEBT, self::TYPE_RECEIVABLE])
            ->sum(DB::raw($this->signedCashCase()));

        // 2b. Net cash change PER HARI untuk periode yang dipilih — dihitung langsung
        // dengan sign yang benar per kategori, jadi tidak perlu digabung manual di PHP.
        $dailyNetRaw = DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->selectRaw('transaction_logs.date as tx_date, SUM('.$this->signedCashCase().') as net_change')
            ->where('transaction_logs.user_id', $userId)
            ->where('transaction_logs.is_cleared', true)
            ->whereBetween('transaction_logs.date', [$startDate, $endDate])
            ->whereIn('transaction_logs.type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE, self::TYPE_DEBT, self::TYPE_RECEIVABLE])
            ->groupBy('transaction_logs.date')
            ->pluck('net_change', 'tx_date');

        // 3. Ambil data Harian (Grouping di level DB) — tetap gross per type_id,
        // dipakai untuk bar "arus kas" (income/expense/debt/receivable terpisah warnanya).
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

            $dInc = (float) ($dayTx->firstWhere('type_id', self::TYPE_INCOME)?->total ?? 0);
            $dExp = (float) ($dayTx->firstWhere('type_id', self::TYPE_EXPENSE)?->total ?? 0);

            $dailyIncome[] = $dInc;
            $dailyExpense[] = $dExp;

            // FIX: pakai net change yang sudah benar sign-nya per kategori,
            // bukan (income + receivable - expense - debt) yang salah arah.
            $runningBalance += (float) ($dailyNetRaw[$dateStr] ?? 0);
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

        $formatCategory = fn ($c) => ['id' => $c->id, 'name' => $c->name, 'icon' => $c->icon, 'total' => (float) $c->total];

        $expensesByCategory = $categoriesRaw->where('type_id', self::TYPE_EXPENSE)->map($formatCategory)->values()->toArray();
        $incomesByCategory = $categoriesRaw->where('type_id', self::TYPE_INCOME)->map($formatCategory)->values()->toArray();
        $debtsByCategory = $categoriesRaw->where('type_id', self::TYPE_DEBT)->map($formatCategory)->values()->toArray();
        $receivablesByCategory = $categoriesRaw->where('type_id', self::TYPE_RECEIVABLE)->map($formatCategory)->values()->toArray();

        // 5. Histori Keseluruhan (Agregasi di DB, output hanya tanggal yang ada transaksinya)
        // FIX: tambah $allDailyDates (raw ISO date) supaya frontend bisa grouping
        // minggu/bulan dengan akurat, bukan parsing string label "d M Y".
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
        $allDailyDates = [];
        $allDailyIncome = [];
        $allDailyExpense = [];
        $allDailyDebt = [];
        $allDailyReceivable = [];

        foreach ($allHistoryRaw as $date => $trxs) {
            $allDailyLabels[] = Carbon::parse($date)->format('d M Y');
            $allDailyDates[] = Carbon::parse($date)->format('Y-m-d');

            $allDailyIncome[] = (float) ($trxs->firstWhere('type_id', self::TYPE_INCOME)?->total ?? 0);
            $allDailyExpense[] = (float) ($trxs->firstWhere('type_id', self::TYPE_EXPENSE)?->total ?? 0);
            $allDailyDebt[] = (float) ($trxs->firstWhere('type_id', self::TYPE_DEBT)?->total ?? 0);
            $allDailyReceivable[] = (float) ($trxs->firstWhere('type_id', self::TYPE_RECEIVABLE)?->total ?? 0);
        }

        // 6. Snapshot Logic (Optimasi kolom fetch)
        // PERHATIAN: kalau snapshot untuk periode ini sudah lengkap, hasil di bawah
        // ini MENIMPA $cumulativeData/$cumulativeBalance yang baru saja diperbaiki.
        // Kalau BuildNetWorthSnapshotsJob masih pakai formula sign yang lama, saldo
        // akan tetap salah walau controller ini sudah benar. Snapshot lama perlu
        // di-rebuild setelah job tsb diperbaiki juga.
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
            'allDailyDates' => $allDailyDates,
            'allDailyIncome' => $allDailyIncome,
            'allDailyExpense' => $allDailyExpense,
            'allDailyDebt' => $allDailyDebt,
            'allDailyReceivable' => $allDailyReceivable,
        ]);
    }
}