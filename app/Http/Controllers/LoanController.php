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

        $view = request()->user()?->ui_theme === 'm3'
            ? 'Material/Loans/Index'
            : 'Loans/Index';

        return Inertia::render($view, [
            'loanDetails' => $loanDetails,
            'title' => $title,
            'isDebt' => $isDebt,
            'total' => $total,
        ]);
    }
}
