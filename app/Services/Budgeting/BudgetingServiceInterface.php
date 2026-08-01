<?php

namespace App\Services\Budgeting;

use App\Models\BudgetGroup;
use App\Models\User;

interface BudgetingServiceInterface
{
    /**
     * Generate a new budget for a user for a given month and year using AI.
     */
    public function generate(User $user, int $month, int $year): BudgetGroup;

    /**
     * Get the budget summary including spent amounts.
     */
    public function getBudgetSummary(BudgetGroup $budgetGroup): array;
}
