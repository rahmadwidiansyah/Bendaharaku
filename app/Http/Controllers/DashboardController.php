<?php

namespace App\Http\Controllers;

use App\Services\Loan\ActiveLoanCycleService;
use App\Services\Transaction\TransactionListService;
use App\Traits\CalculatesDebtAndReceivable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use CalculatesDebtAndReceivable;

    // Constant mapping untuk menghindari query `whereHas` berulang kali
    private const TYPE_INCOME = 1;

    private const TYPE_EXPENSE = 2;

    private const TYPE_TRANSFER = 3;

    private const TYPE_DEBT = 4;

    private const TYPE_RECEIVABLE = 5;

    private const TYPE_MAP = [
        'income' => self::TYPE_INCOME,
        'expense' => self::TYPE_EXPENSE,
        'transfer' => self::TYPE_TRANSFER,
        'debt' => self::TYPE_DEBT,
        'receivable' => self::TYPE_RECEIVABLE,
    ];

    public function index(Request $request, TransactionListService $lists): Response
    {
        $userId = Auth::id();
        $user = Auth::user();

        $now = Carbon::now();

        // 1. DATA ANALISIS PORTFOLIO (1 Query agregasi database)
        $walletStats = DB::table('wallets')
            ->where('user_id', $userId)
            ->whereIn('group_type', ['Asset', 'Liquid'])
            ->selectRaw("
                COALESCE(SUM(balance), 0) as total_portfolio,
                COALESCE(SUM(CASE WHEN group_type = 'Liquid' THEN balance ELSE 0 END), 0) as total_liquid,
                COALESCE(SUM(CASE WHEN group_type = 'Asset' THEN balance ELSE 0 END), 0) as total_invest
            ")
            ->first();

        // 2. DATA ARUS KAS BULAN INI (1 Query agregasi database menggunakan whereBetween)
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $cashflowStats = DB::table('transaction_logs')
            ->where('user_id', $userId)
            ->where('is_cleared', true)
            ->whereNull('deleted_at')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereIn('type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE])
            ->selectRaw('
                COALESCE(SUM(CASE WHEN type_id = '.self::TYPE_INCOME.' THEN amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN type_id = '.self::TYPE_EXPENSE.' THEN amount ELSE 0 END), 0) as total_expense
            ')
            ->first();

        // 3. PINNED WALLETS
        // Kolom dibatasi hanya yang benar-benar dipakai di frontend (id, name, icon, balance, group_type, is_pinned)
        $pinnedWallets = $user->wallets()
            ->select(['id', 'name', 'icon', 'balance', 'group_type', 'is_pinned'])
            ->where('is_pinned', true)
            ->get();

        if ($pinnedWallets->count() < 4) {
            $pinnedIds = $pinnedWallets->pluck('id')->toArray();

            $fallbackWallets = $user->wallets()
                ->select(['id', 'name', 'icon', 'balance', 'group_type', 'is_pinned'])
                ->whereNotIn('id', $pinnedIds)
                ->whereNull('is_pinned')
                ->where('group_type', '!=', 'System')
                ->withCount(['sourceTransactions', 'destinationTransactions'])
                ->get()
                ->sortByDesc(fn ($w) => $w->source_transactions_count + $w->destination_transactions_count)
                ->take(4 - $pinnedWallets->count())
                ->values();

            $fallbackWallets->each(function ($w) {
                $w->is_virtual_pin = true;
            });

            $pinnedWallets = $pinnedWallets->concat($fallbackWallets)->values();
        }

        $pinnedWallets = $pinnedWallets->take(4);

        // Semua wallets untuk dropdown di PortfolioCard
        $wallets = $user->wallets()
            ->select(['id', 'name', 'icon', 'balance', 'group_type', 'is_pinned'])
            ->where('group_type', '!=', 'System')
            ->orderBy('id')
            ->get();

        // 4-5. HISTORI TRANSAKSI (log + draft) via paginasi keyset UNION ALL.
        // Halaman pertama di sini; halaman berikut via endpoint transactions.page.
        // Subtotal grup & marker kalender dihitung server dari SELURUH rentang
        // (bukan dari baris halaman) agar benar saat daftar dipaginasi.
        $startDate = $request->input('start_date', $startOfMonth);
        $endDate = $request->input('end_date', $endOfMonth);

        $listFilters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => $request->input('type', ''),
            'search' => $request->input('search', ''),
        ];
        $listLimit = max(1, min(TransactionListService::MAX_LIMIT, (int) $request->input('limit', TransactionListService::DEFAULT_LIMIT)));

        $listPage = $lists->paginate($user, $listFilters, null, $listLimit);
        $listAgg = $lists->aggregates($user, $listFilters);
        $transactions = $listPage['data'];

        // 6. NOTIFIKASI UPCOMING DEBT
        $upcomingDebts = [];
        $nowStart = Carbon::now()->startOfDay();
        $activeCycles = app(ActiveLoanCycleService::class)->calculateForUser($user, $nowStart);

        foreach (array_merge($activeCycles['debts'], $activeCycles['receivables']) as $cycle) {
            if (! $cycle['due_date']) {
                continue;
            }

            $daysUntilDue = (int) $nowStart->diffInDays($cycle['due_date'], false);
            if ($daysUntilDue <= 7) {
                $upcomingDebts[] = [
                    'subject' => $cycle['subject'],
                    'type' => $cycle['type'] === 'debt' ? 'Hutang' : 'Piutang',
                    'remaining' => $cycle['balance'],
                    'days_until' => $daysUntilDue,
                    'next_due_date' => $cycle['due_date']->format('d M Y'),
                ];
            }
        }

        usort($upcomingDebts, function ($a, $b) {
            return $a['days_until'] <=> $b['days_until'];
        });

        // Pakai ulang hasil pindai loan di atas ($activeCycles) alih-alih
        // memanggil calculateAllBalances() yang menjalankan pindai penuh
        // kedua untuk user dan tanggal yang sama. Total tidak bergantung
        // pada presisi waktu $today (hanya due_date/age yang bergantung,
        // dan itu sudah dihitung di $activeCycles).
        $balances = [
            'total_debt' => $activeCycles['total_debt'] ?? 0,
            'total_receivable' => $activeCycles['total_receivable'] ?? 0,
        ];

        return Inertia::render('Dashboard', [
            'totalPortfolio' => (int) $walletStats->total_portfolio,
            'totalLiquid' => (int) $walletStats->total_liquid,
            'totalInvest' => (int) $walletStats->total_invest,
            'totalHutang' => $balances['total_debt'],
            'totalPiutang' => $balances['total_receivable'],
            'thisMonthIncome' => (int) $cashflowStats->total_income,
            'thisMonthExpense' => (int) $cashflowStats->total_expense,
            'pinnedWallets' => $pinnedWallets,
            'wallets' => $wallets,
            'transactions' => [
                'data' => $transactions,
                'next_cursor' => $listPage['next_cursor'],
                'has_more' => $listPage['has_more'],
            ],
            'dailyTotals' => $listAgg['dailyTotals'],
            'calendarMarkers' => $listAgg['markers'],
            'listLimit' => $listLimit,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => $request->only(['type']),
            'upcomingDebts' => $upcomingDebts,
        ]);
    }
}
