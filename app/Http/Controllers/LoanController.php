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
            ->get();

        // Grouping per orang (Subject)
        $loanDetails = $transactions->groupBy('subject')->map(function ($txs) use ($isDebt) {
            $balance = 0;

            foreach ($txs as $tx) {
                $catName = $tx->category->category_name;
                if ($isDebt) {
                    if ($catName === 'Dapat Hutangan') {
                        $balance += $tx->amount;
                    } elseif ($catName === 'Bayar Cicilan Hutang') {
                        $balance -= $tx->amount;
                    }
                } else {
                    if ($catName === 'Ngasih Piutang') {
                        $balance += $tx->amount;
                    } elseif ($catName === 'Terima Bayar Piutang') {
                        $balance -= $tx->amount;
                    }
                }
            }

            $sorted = $txs->sortBy('date');
            $firstDate = $sorted->first()->date;
            $latestDate = $sorted->last()->date;

            return (object) [
                'subject' => $txs->first()->subject,
                'balance' => $balance,
                'age' => $firstDate ? intval(Carbon::parse($firstDate)->diffInDays(now())) : 0,
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
