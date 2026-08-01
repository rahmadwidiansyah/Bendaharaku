<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetExpenseGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_group_id',
        'group_key',
        'group_name',
        'category_ids',
    ];

    protected $casts = [
        'category_ids' => 'array',
    ];

    public function budgetGroup(): BelongsTo
    {
        return $this->belongsTo(BudgetGroup::class);
    }
}
