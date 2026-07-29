<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoanController extends Controller
{
    public function index($type)
    {
        $user = Auth::user();
        // Support 'debt' (baru) dan 'hutang' (lama) agar backward compatible
        $isDebt = in_array($type, ['debt', 'hutang']);

        $transactions = $user->transactionLogs()->with('category')
            ->whereHas('category', function ($q) use ($isDebt) {
                if ($isDebt) {
                    $q->whereIn('category_name', ['Dapat Hutangan', 'Bayar Cicilan Hutang']);
                } else {
                    $q->whereIn('category_name', ['Ngasih Piutang', 'Terima Bayar Piutang']);
                }
            })
            ->whereNotNull('subject')
            ->where('subject', '!=', '-')
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $increaseCategory = $isDebt ? 'Dapat Hutangan' : 'Ngasih Piutang';
        $decreaseCategory = $isDebt ? 'Bayar Cicilan Hutang' : 'Terima Bayar Piutang';

        // Grouping per orang (Subject, case-insensitive), kronologis per subject
        // supaya "since" (tanggal mulai siklus aktif) reset setiap kali saldo
        // sempat balik ke 0 dan orang itu berhutang/piutang lagi.
        $loanDetails = $transactions->groupBy(fn ($tx) => strtoupper($tx->subject))->map(function ($txs) use ($increaseCategory, $decreaseCategory) {
            $txs = $txs->sortBy(fn ($tx) => [$tx->date, $tx->created_at])->values();

            $balance = 0;
            $since = null;
            $latestDate = null;

            foreach ($txs as $tx) {
                $catName = $tx->category->category_name;

                if ($catName === $increaseCategory) {
                    // Kalau saldo sebelumnya 0 (lunas / belum pernah), ini siklus baru
                    if ($balance <= 0) {
                        $since = $tx->date;
                    }
                    $balance += $tx->amount;
                } elseif ($catName === $decreaseCategory) {
                    $balance -= $tx->amount;
                    if ($balance < 0) {
                        $balance = 0; // jaga-jaga kalau ada overpay
                    }
                }

                $latestDate = $tx->date;
            }

            return (object) [
                'subject' => $txs->first()->subject,
                'balance' => $balance,
                'age' => $since ? intval(Carbon::parse($since)->diffInDays(now())) : 0,
                'since' => $since,
                'latest_date' => $latestDate,
            ];
        })
            ->filter(fn ($item) => $item->balance > 0) // Hanya muncul yang belum lunas
            ->sortBy('subject') // SORT BERDASARKAN NAMA (A-Z)
            ->values(); // Reset key agar Inertia mengirim JSON array, bukan object

        $title = $isDebt ? 'Rincian Hutang' : 'Rincian Piutang';
        $total = $loanDetails->sum('balance');
        $loanDetails = $loanDetails->all();

        return Inertia::render('Loans/Index', compact('loanDetails', 'title', 'isDebt', 'total'));
    }
}