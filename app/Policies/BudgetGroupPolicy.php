<?php

namespace App\Policies;

use App\Models\BudgetGroup;
use App\Models\User;

class BudgetGroupPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BudgetGroup $budgetGroup): bool
    {
        return $user->id === $budgetGroup->user_id;
    }
}
