<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LoanController extends Controller
{
    public function index($type)
{
    $user = Auth::user();
    $isDebt = $type === 'hutang';
    $systemWalletName = $isDebt ? 'System Hutang' : 'System Piutang';
    $systemWallet = $user->wallets()->where('name', $systemWalletName)->first();

    if (!$systemWallet) return redirect()->route('dashboard');

    $transactions = $user->transactionLogs()
        ->where(function($q) use ($systemWallet) {
            $q->where('source_wallet_id', $systemWallet->id)
              ->orWhere('destination_wallet_id', $systemWallet->id);
        })
        ->where('subject', '!=', '-')
        ->get();

    // Grouping per orang (Subject)
    $loanDetails = $transactions->groupBy('subject')->map(function($txs) use ($isDebt, $systemWallet) {
        $balance = 0;
        $latestDate = null;
        $firstDate = null;

        foreach($txs as $tx) {
            if ($isDebt) {
                // HUTANG: Keluar dari System = Nambah Utang, Masuk ke System = Bayar
                if ($tx->source_wallet_id == $systemWallet->id) {
                    $balance += $tx->amount;
                    if (!$firstDate) $firstDate = $tx->date;
                } else {
                    $balance -= $tx->amount;
                }
            } else {
                // PIUTANG: Masuk ke System = Kasih Pinjam, Keluar dari System = Orang Bayar
                if ($tx->destination_wallet_id == $systemWallet->id) {
                    $balance += $tx->amount;
                    if (!$firstDate) $firstDate = $tx->date;
                } else {
                    $balance -= $tx->amount;
                }
            }
            $latestDate = $tx->date;
        }

        return (object)[
            'subject' => $txs->first()->subject,
            'balance' => $balance,
            'age' => $firstDate ? intval(Carbon::parse($firstDate)->diffInDays(now())) : 0,
            'latest_date' => $latestDate
        ];
    })
    ->filter(fn($item) => $item->balance > 0) // Hanya muncul yang belum lunas
    ->values()
    ->sortBy('subject'); // SORT BERDASARKAN NAMA (A-Z)

    $title = $isDebt ? 'Rincian Hutang' : 'Rincian Piutang';
    $total = $loanDetails->sum('balance');

    return view('loans.index', compact('loanDetails', 'title', 'isDebt', 'total'));
}
}
