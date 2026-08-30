<?php

namespace App\Traits;

use App\Services\Loan\ActiveLoanCycleService;
use Illuminate\Support\Facades\Auth;

trait CalculatesDebtAndReceivable
{
    public function calculateAllBalances(): array
    {
        return app(ActiveLoanCycleService::class)->calculateForUser(Auth::user());
    }
}
