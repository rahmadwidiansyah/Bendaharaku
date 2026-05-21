<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\TransactionLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // 1. DATA ANALISIS ARUS KAS BULAN INI (Hanya untuk ringkasan di Dashboard)
        $totalPortfolio = $user->wallets()
            ->whereIn('group_type', ['Asset', 'Liquid'])
            ->sum('balance');
        
        $totalLiquid = $user->wallets()->where('group_type', 'Liquid')->sum('balance');
        $totalInvest = $user->wallets()->where('group_type', 'Asset')->sum('balance');

        // 5. DATA ANALISIS ARUS KAS BULAN INI
        $now = Carbon::now();
        $thisMonthIncome = $user->transactionLogs()
            ->whereHas('type', function($q){ $q->where('name', 'Income'); })
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');

        $thisMonthExpense = $user->transactionLogs()
            ->whereHas('type', function($q){ $q->where('name', 'Expense'); })
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');

        // Pinned Wallets (Atau default dompet paling sering digunakan hingga maksimal 4)
        $pinnedWallets = $user->wallets()->where('is_pinned', true)->get();

        if ($pinnedWallets->count() < 4) {
            $pinnedIds = $pinnedWallets->pluck('id')->toArray();
            
            $fallbackWallets = $user->wallets()
                ->whereNotIn('id', $pinnedIds)
                ->whereNull('is_pinned') // Hanya ambil dompet yang belum pernah di-pin atau di-unpin secara manual
                ->where('group_type', '!=', 'System')
                ->withCount(['sourceTransactions', 'destinationTransactions'])
                ->get()
                ->sortByDesc(function ($wallet) {
                    return $wallet->source_transactions_count + $wallet->destination_transactions_count;
                })
                ->take(4 - $pinnedWallets->count())
                ->values();
            
            // Tandai dompet fallback agar frontend tahu bahwa ini adalah "virtual pin"
            $fallbackWallets->each(function($w) {
                $w->is_virtual_pin = true;
            });
                
            $pinnedWallets = $pinnedWallets->concat($fallbackWallets)->values();
        }

        // Pastikan maksimal hanya 4 yang ditampilkan
        $pinnedWallets = $pinnedWallets->take(4);

        // 6. LOGIKA HISTORI TRANSAKSI (Pindahan dari TransactionController)
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $query = $user->transactionLogs()->with(['type', 'category', 'sourceWallet', 'destinationWallet']);
        $query->whereBetween('date', [$startDate, $endDate]);

        if ($request->has('type') && $request->type != '') {
            $query->whereHas('type', function($q) use ($request) {
                $q->where('name', $request->type);
            });
        }

        if ($request->has('search') && $request->search != '') {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(notes) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(subject) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('category', function($qCat) use ($search) {
                      $qCat->whereRaw('LOWER(category_name) LIKE ?', ["%{$search}%"]);
                  });
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
                                      'date' => Carbon::parse($trx->date)->translatedFormat('d M Y'),
                                      'raw_date' => $trx->date,
                                      'time' => Carbon::parse($trx->created_at)->format('H:i'),
                                      'type' => $trx->type,
                                      'category' => $trx->category,
                                      'source_wallet' => $trx->sourceWallet,
                                      'destination_wallet' => $trx->destinationWallet,
                                  ];
                              });

        return Inertia::render('Dashboard', [
            'totalPortfolio' => (int) $totalPortfolio,
            'totalLiquid' => (int) $totalLiquid,
            'totalInvest' => (int) $totalInvest,
            'thisMonthIncome' => (int) $thisMonthIncome,
            'thisMonthExpense' => (int) $thisMonthExpense,
            'pinnedWallets' => $pinnedWallets,
            'transactions' => [
                'data' => $transactions
            ],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => $request->only(['search', 'type']),
        ]);
    }
}