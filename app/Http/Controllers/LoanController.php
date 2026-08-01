<?php

namespace App\Http\Controllers;

use App\Traits\CalculatesDebtAndReceivable;
use Inertia\Inertia;

class LoanController extends Controller
{
    use CalculatesDebtAndReceivable;

    public function index($type)
    {
        // Support 'debt' (baru) dan 'hutang' (lama) agar backward compatible
        $isDebt = in_array($type, ['debt', 'hutang']);

        $balances = $this->calculateAllBalances();

        if ($isDebt) {
            $loanDetails = $balances['debts'];
            $total = $balances['total_debt'];
            $title = 'Rincian Hutang';
        } else {
            $loanDetails = $balances['receivables'];
            $total = $balances['total_receivable'];
            $title = 'Rincian Piutang';
        }

        return Inertia::render('Loans/Index', [
            'loanDetails' => $loanDetails,
            'title' => $title,
            'isDebt' => $isDebt,
            'total' => $total,
        ]);
    }
}
