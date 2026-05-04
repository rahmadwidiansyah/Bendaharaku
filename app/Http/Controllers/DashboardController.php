<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\TransactionLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        // 1. DOMPET AKTIF (Diurutkan dari saldo paling besar)
        $wallets = $user->wallets()
            ->whereIn('group_type', ['Asset', 'Liquid'])
            ->orderByDesc('balance') 
            ->get();

        // 2. TOTAL PORTOFOLIO (Jumlah aset riil)
        $totalPortfolio = $wallets->sum('balance');

        // Breakdown Liquid & Investasi
        $totalLiquid = $wallets->where('group_type', 'Liquid')->sum('balance');
        $totalInvest = $wallets->where('group_type', 'Asset')->sum('balance');

        // 3. LOGIKA HITUNG HUTANG
        $systemHutang = $user->wallets()->where('name', 'like', '%Hutang%')->first();
        $totalHutang = 0;
        if ($systemHutang) {
            $debtIn = $user->transactionLogs()->where('source_wallet_id', $systemHutang->id)->sum('amount');
            $debtPaid = $user->transactionLogs()->where('destination_wallet_id', $systemHutang->id)->sum('amount');
            $totalHutang = $debtIn - $debtPaid;
        }

        // 4. LOGIKA HITUNG PIUTANG
        $systemPiutang = $user->wallets()->where('name', 'like', '%Piutang%')->first();
        $totalPiutang = 0;
        if ($systemPiutang) {
            $receivableOut = $user->transactionLogs()->where('destination_wallet_id', $systemPiutang->id)->sum('amount');
            $receivableIn = $user->transactionLogs()->where('source_wallet_id', $systemPiutang->id)->sum('amount');
            $totalPiutang = $receivableOut - $receivableIn;
        }

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

        // 6. TRANSAKSI TERAKHIR (5 Data Terbaru)
        $recentTransactions = $user->transactionLogs()
            ->with(['category', 'type', 'sourceWallet', 'destinationWallet']) // Eager load relasi biar cepat
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($trx) {
                return [
                    'id' => $trx->id,
                    'amount' => $trx->amount,
                    'notes' => $trx->notes,
                    'subject' => $trx->subject,
                    'date' => Carbon::parse($trx->date)->translatedFormat('d M Y'),
                    'time' => Carbon::parse($trx->created_at)->format('H:i'),
                    'short_date' => Carbon::parse($trx->date)->format('d M'),
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
            'wallets' => $wallets,
            'totalHutang' => (int) max(0, $totalHutang),
            'totalPiutang' => (int) max(0, $totalPiutang),
            'thisMonthIncome' => (int) $thisMonthIncome,
            'thisMonthExpense' => (int) $thisMonthExpense,
            'recentTransactions' => $recentTransactions 
        ]);
    }
}