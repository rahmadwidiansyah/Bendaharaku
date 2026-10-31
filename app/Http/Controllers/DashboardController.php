<?php

namespace App\Http\Controllers;

use App\Models\AiParseLog;
use App\Models\BudgetGroup;
use App\Services\Budgeting\BudgetingServiceInterface;
use App\Services\Loan\ActiveLoanCycleService;
use App\Support\TransactionTypeIds;
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

    public function __construct(
        private readonly BudgetingServiceInterface $budgetService,
    ) {}

    // Id tipe transaksi di-resolve dari DB by name (jangan hardcode —
    // id tabel transaction_types di-generate seeder dan bisa bergeser).

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
            ->whereIn('type_id', [TransactionTypeIds::income(), TransactionTypeIds::expense()])
            ->selectRaw('
                COALESCE(SUM(CASE WHEN type_id = '.TransactionTypeIds::income().' THEN amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN type_id = '.TransactionTypeIds::expense().' THEN amount ELSE 0 END), 0) as total_expense
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

        // Multi-UI Phase 1: pilih komponen halaman sesuai preferensi user.
        // Pengumpulan data & payload props identik untuk kedua tampilan.
        $view = $request->user()->ui_theme === 'm3' ? 'Material/Dashboard' : 'Dashboard';

        return Inertia::render($view, [
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
            'budget' => $this->buildBudgetWidget($userId),
            'aiUsage' => $this->buildAiUsageWidget($userId),
        ]);
    }

    /**
     * Widget "Ringkasan budget bulan ini" untuk dashboard M3.
     * Reuse getBudgetSummary() — tanpa logika hitung baru. Null bila user
     * belum membuat budget bulan berjalan (kartu disembunyikan di frontend).
     */
    private function buildBudgetWidget(int $userId): ?array
    {
        $group = BudgetGroup::where('user_id', $userId)
            ->where('period_month', now()->format('n'))
            ->where('period_year', now()->format('Y'))
            ->with(['items.budgetable'])
            ->first();

        if (! $group || $group->items->isEmpty()) {
            return null;
        }

        $summary = $this->budgetService->getBudgetSummary($group);

        $items = [];
        foreach ($group->items as $item) {
            $s = $summary[$item->id] ?? null;
            $name = $item->budgetable?->category_name ?? $item->budgetable?->name ?? null;
            if ($s === null || $name === null) {
                continue;
            }
            $items[] = [
                'name' => $name,
                'target' => (float) $s['target'],
                'spent' => (float) $s['spent'],
                'percentage' => (float) $s['percentage'],
            ];
        }

        if ($items === []) {
            return null;
        }

        $totalBudget = array_sum(array_column($items, 'target'));
        $totalSpent = array_sum(array_column($items, 'spent'));

        // Top 3 kategori dengan target terbesar — paling representatif di kartu mini.
        usort($items, fn (array $a, array $b) => $b['target'] <=> $a['target']);

        return [
            'totalBudget' => $totalBudget,
            'totalSpent' => $totalSpent,
            'percentage' => $totalBudget > 0 ? min(100, round(($totalSpent / $totalBudget) * 100, 1)) : 0,
            'items' => array_slice($items, 0, 3),
        ];
    }

    /**
     * Widget "Pemakaian AI hari ini": jumlah pesan diproses, token, success
     * rate — data nyata dari ai_parse_logs. Null bila belum ada pemakaian.
     */
    private function buildAiUsageWidget(int $userId): ?array
    {
        $row = AiParseLog::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->selectRaw('COUNT(*) as requests, COALESCE(SUM(total_tokens), 0) as tokens, COALESCE(AVG(CASE WHEN is_success THEN 1.0 ELSE 0 END), 0) as success_rate')
            ->first();

        if (! $row || (int) $row->requests === 0) {
            return null;
        }

        return [
            'requests' => (int) $row->requests,
            'tokens' => (int) $row->tokens,
            'successRate' => round((float) $row->success_rate * 100, 1),
        ];
    }
}
