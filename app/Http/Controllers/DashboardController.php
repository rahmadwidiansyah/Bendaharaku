<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\TransactionLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. DOMPET AKTIF (Diurutkan dari saldo paling besar)
        $wallets = $user->wallets()
            ->whereIn('group_type', ['Asset', 'Liquid'])
            ->orderByDesc('balance') 
            ->get();

        // 2. TOTAL PORTOFOLIO (Jumlah aset riil)
        $totalPortfolio = $wallets->sum('balance');

        // ==========================================
        // TAMBAHAN: BREAKDOWN LIQUID & INVESTASI
        // ==========================================
        // Menggunakan Collection filter agar tidak perlu query ke database dua kali
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
            ->with(['category', 'type']) // Eager load relasi biar cepat
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'totalPortfolio' => $totalPortfolio,
            'totalLiquid' => $totalLiquid, // Passing data Liquid
            'totalInvest' => $totalInvest, // Passing data Investasi
            'wallets' => $wallets,
            'totalHutang' => max(0, $totalHutang),
            'totalPiutang' => max(0, $totalPiutang),
            'thisMonthIncome' => $thisMonthIncome,
            'thisMonthExpense' => $thisMonthExpense,
            'recentTransactions' => $recentTransactions 
        ]);
    }
}