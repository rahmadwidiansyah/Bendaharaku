<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
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

    public function index(Request $request): Response
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
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereIn('type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type_id = " . self::TYPE_INCOME . " THEN amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN type_id = " . self::TYPE_EXPENSE . " THEN amount ELSE 0 END), 0) as total_expense
            ")
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
                ->sortByDesc(fn($w) => $w->source_transactions_count + $w->destination_transactions_count)
                ->take(4 - $pinnedWallets->count())
                ->values();

            $fallbackWallets->each(function ($w) {
                $w->is_virtual_pin = true;
            });

            $pinnedWallets = $pinnedWallets->concat($fallbackWallets)->values();
        }

        $pinnedWallets = $pinnedWallets->take(4);

        // 4. HISTORI TRANSAKSI
        $startDate = $request->input('start_date', $startOfMonth);
        $endDate = $request->input('end_date', $endOfMonth);

        $query = $user->transactionLogs()
            // Batasi kolom tabel utama sesuai yang dipakai di ->map() di bawah,
            // supaya Eloquent tidak menghidrasi kolom yang tidak perlu.
            ->select([
                'id',
                'user_id',
                'amount',
                'notes',
                'subject',
                'is_cleared',
                'reference_number',
                'date',
                'created_at',
                'type_id',
                'category_id',
                'source_wallet_id',
                'destination_wallet_id',
                'due_date',
                'due_date_type',
                'due_date_interval',
            ])
            ->with([
                'type:id,name',
                'category:id,category_name,icon',
                'sourceWallet:id,name,group_type',
                'destinationWallet:id,name',
            ])
            ->where('is_cleared', true)
            ->whereBetween('date', [$startDate, $endDate]);

        // Filter by Type (Menggunakan ID, bukan Relasi/whereHas)
        if ($request->has('type') && $request->type != '') {
            $reqType = strtolower($request->type);
            if (isset(self::TYPE_MAP[$reqType])) {
                $query->where('type_id', self::TYPE_MAP[$reqType]);
            }
        }

        // Search Filter (Menggunakan ILIKE bawaan PostgreSQL yang jauh lebih cepat)
        if ($request->has('search') && $request->search != '') {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                if (ctype_digit($search)) {
                    $q->where('id', (int) $search)
                        ->orWhere('reference_number', 'ILIKE', "%{$search}%");
                } else {
                    $q->where('notes', 'ILIKE', "%{$search}%")
                        ->orWhere('subject', 'ILIKE', "%{$search}%")
                        ->orWhere('reference_number', 'ILIKE', "%{$search}%")
                        ->orWhereHas('category', function ($qCat) use ($search) {
                            $qCat->where('category_name', 'ILIKE', "%{$search}%");
                        });
                }
            });
        }

        $transactions = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($trx) {
                return [
                    'id' => $trx->id,
                    'amount' => (float) $trx->amount,
                    'notes' => $trx->notes,
                    'subject' => $trx->subject,
                    'is_cleared' => (bool) $trx->is_cleared,
                    'reference_number' => $trx->reference_number,
                    'date' => Carbon::parse($trx->date)->translatedFormat('d M Y'),
                    'raw_date' => Carbon::parse($trx->date)->format('Y-m-d'),
                    'time' => Carbon::parse($trx->created_at)->format('H:i'),
                    'type' => $trx->type,
                    'category' => $trx->category,
                    'source_wallet' => $trx->sourceWallet,
                    'destination_wallet' => $trx->destinationWallet,
                    'due_date' => $trx->due_date,
                    'due_date_type' => $trx->due_date_type,
                    'due_date_interval' => $trx->due_date_interval,
                ];
            });

        // 5. TRANSAKSI DRAFTS
        $pendingDraftsQuery = $user->transactionDrafts()->where('status', 'pending')
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        // Optimasi: PostgreSQL native JSON ILIKE
        if ($request->filled('type')) {
            $type = strtolower($request->type);
            $pendingDraftsQuery->whereRaw("payload->>'type_key' ILIKE ?", [$type]);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $pendingDraftsQuery->where(function ($q) use ($search) {
                $q->where('original_text', 'ILIKE', "%{$search}%")
                    ->orWhereRaw("payload->>'notes' ILIKE ?", ["%{$search}%"])
                    ->orWhereRaw("payload->>'subject' ILIKE ?", ["%{$search}%"])
                    ->orWhereRaw("payload->>'category_name' ILIKE ?", ["%{$search}%"]);
            });
        }

        $draftData = $pendingDraftsQuery->orderBy('created_at', 'desc')->get()->map(function ($draft) {
            $payload = $draft->payload ?? [];
            return [
                'id' => $draft->id,
                'is_draft' => true,
                'draft_id' => $draft->id,
                'amount' => (float) ($payload['amount'] ?? 0),
                'notes' => $payload['notes'] ?? $draft->original_text,
                'subject' => $payload['subject'] ?? '-',
                'is_cleared' => false,
                'reference_number' => null,
                'date' => Carbon::parse($payload['date'] ?? $draft->created_at)->translatedFormat('d M Y'),
                'raw_date' => Carbon::parse($payload['date'] ?? $draft->created_at)->format('Y-m-d'),
                'time' => Carbon::parse($draft->created_at)->format('H:i'),
                'type' => [
                    'id' => null,
                    'name' => ucfirst($payload['type_key'] ?? 'expense'),
                ],
                'category' => isset($payload['category_name']) ? [
                    'id' => $payload['category_id'] ?? null,
                    'category_name' => $payload['category_name'],
                ] : null,
                'source_wallet' => isset($payload['source_wallet_name']) ? [
                    'id' => $payload['source_wallet_id'] ?? null,
                    'name' => $payload['source_wallet_name'],
                ] : null,
                'destination_wallet' => isset($payload['destination_wallet_name']) ? [
                    'id' => $payload['destination_wallet_id'] ?? null,
                    'name' => $payload['destination_wallet_name'],
                ] : null,
                'due_date' => null,
                'due_date_type' => null,
                'due_date_interval' => null,
            ];
        });

        $transactions = $transactions->concat($draftData)->sort(function ($a, $b) {
            $dateCompare = strcmp($b['raw_date'], $a['raw_date']);
            if ($dateCompare !== 0) return $dateCompare;

            $timeCompare = strcmp($b['time'], $a['time']);
            if ($timeCompare !== 0) return $timeCompare;

            return $b['id'] <=> $a['id'];
        })->values();

        // 6. NOTIFIKASI UPCOMING DEBT (Penghapusan Loop N+1 Query)
        $upcomingDebts = [];

        // Optimasi: Hanya ambil kolom yang benar-benar dibutuhkan
        $debtsWithDueDate = $user->transactionLogs()
            ->with('category:id,category_name')
            ->where('is_cleared', true)
            ->whereNotNull('due_date_type')
            ->select(['id', 'subject', 'category_id', 'date', 'due_date', 'due_date_type', 'due_date_interval'])
            ->get();

        $processedSubjects = [];
        $subjectsToQuery = [];
        $subjectDetails = [];
        $nowStart = Carbon::now()->startOfDay();

        // 6a. Menentukan Subject mana saja yang jatuh tempo <= 7 hari (Murni komputasi CPU, 0 Query)
        foreach ($debtsWithDueDate as $trx) {
            if (!$trx->category || !$trx->subject) continue;

            $catName = strtolower($trx->category->category_name);
            $isDebt = str_contains($catName, 'dapat hutang');
            $isReceivable = str_contains($catName, 'ngasih piutang');

            if (!$isDebt && !$isReceivable) continue;

            $cacheKey = $trx->subject.'_'.($isDebt ? 'debt' : 'receivable');
            if (isset($processedSubjects[$cacheKey])) continue;
            $processedSubjects[$cacheKey] = true;

            $nextDueDate = null;

            if ($trx->due_date_type === 'fixed' && $trx->due_date) {
                $nextDueDate = Carbon::parse($trx->due_date)->startOfDay();
            } elseif ($trx->due_date_type === 'monthly' && $trx->due_date_interval) {
                $day = min(31, max(1, $trx->due_date_interval));
                $nextDueDate = Carbon::now()->setDay($day)->startOfDay();
                if ($nextDueDate->isBefore($nowStart)) {
                    $nextDueDate->addMonth();
                }
            } elseif ($trx->due_date_type === 'daily' && $trx->due_date_interval) {
                $start = Carbon::parse($trx->date)->startOfDay();
                $interval = $trx->due_date_interval;
                $diff = $nowStart->diffInDays($start);

                if ($start->isAfter($nowStart)) {
                    $nextDueDate = $start;
                } else {
                    $cyclesPassed = floor($diff / $interval);
                    $nextDueDate = $start->copy()->addDays(($cyclesPassed + 1) * $interval);
                }
            }

            if ($nextDueDate) {
                $daysUntilDue = (int) $nowStart->diffInDays($nextDueDate, false);

                if ($daysUntilDue <= 7) {
                    $subjectsToQuery[] = $trx->subject;
                    $subjectDetails[$trx->subject][$isDebt ? 'Hutang' : 'Piutang'] = [
                        'days_until' => $daysUntilDue,
                        'next_due_date' => $nextDueDate->format('d M Y'),
                    ];
                }
            }
        }

        // 6b. ONE Single Query untuk menghitung semua sisa hutang/piutang sekaligus
        if (!empty($subjectsToQuery)) {
            $balancesRaw = DB::table('transaction_logs')
                ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
                ->where('transaction_logs.user_id', $userId)
                ->where('transaction_logs.is_cleared', true)
                ->whereIn('transaction_logs.subject', array_unique($subjectsToQuery))
                ->select('transaction_logs.subject')
                ->selectRaw("
                    SUM(CASE WHEN categories.category_name = 'Dapat Hutangan' THEN amount ELSE 0 END) as debt_borrowed,
                    SUM(CASE WHEN categories.category_name = 'Bayar Cicilan Hutang' THEN amount ELSE 0 END) as debt_paid,
                    SUM(CASE WHEN categories.category_name = 'Ngasih Piutang' THEN amount ELSE 0 END) as rec_borrowed,
                    SUM(CASE WHEN categories.category_name = 'Terima Bayar Piutang' THEN amount ELSE 0 END) as rec_paid
                ")
                ->groupBy('transaction_logs.subject')
                ->get();

            foreach ($balancesRaw as $row) {
                $subject = $row->subject;

                if (isset($subjectDetails[$subject]['Hutang'])) {
                    $remainingDebt = $row->debt_borrowed - $row->debt_paid;
                    if ($remainingDebt > 0) {
                        $upcomingDebts[] = [
                            'subject' => $subject,
                            'type' => 'Hutang',
                            'remaining' => $remainingDebt,
                            'days_until' => $subjectDetails[$subject]['Hutang']['days_until'],
                            'next_due_date' => $subjectDetails[$subject]['Hutang']['next_due_date'],
                        ];
                    }
                }

                if (isset($subjectDetails[$subject]['Piutang'])) {
                    $remainingRec = $row->rec_borrowed - $row->rec_paid;
                    if ($remainingRec > 0) {
                        $upcomingDebts[] = [
                            'subject' => $subject,
                            'type' => 'Piutang',
                            'remaining' => $remainingRec,
                            'days_until' => $subjectDetails[$subject]['Piutang']['days_until'],
                            'next_due_date' => $subjectDetails[$subject]['Piutang']['next_due_date'],
                        ];
                    }
                }
            }
        }

        usort($upcomingDebts, function ($a, $b) {
            return $a['days_until'] <=> $b['days_until'];
        });

        return Inertia::render('Dashboard', [
            'totalPortfolio' => (int) $walletStats->total_portfolio,
            'totalLiquid' => (int) $walletStats->total_liquid,
            'totalInvest' => (int) $walletStats->total_invest,
            'thisMonthIncome' => (int) $cashflowStats->total_income,
            'thisMonthExpense' => (int) $cashflowStats->total_expense,
            'pinnedWallets' => $pinnedWallets,
            'transactions' => [
                'data' => $transactions,
            ],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => $request->only(['search', 'type']),
            'upcomingDebts' => $upcomingDebts,
        ]);
    }
}