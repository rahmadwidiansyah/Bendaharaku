<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BudgetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_group_id',
        'budgetable_id',
        'budgetable_type',
        'target_amount',
    ];

    public function budgetGroup(): BelongsTo
    {
        return $this->belongsTo(BudgetGroup::class);
    }

    public function budgetable(): MorphTo
    {
        return $this->morphTo();
    }
}
