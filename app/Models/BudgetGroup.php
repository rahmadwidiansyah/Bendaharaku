<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_month',
        'period_year',
        'name',
        'total_budget_amount',
        'ai_notes',
        'generated_by',
        'over_alert_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'over_alert_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function expenseGroups(): HasMany
    {
        return $this->hasMany(BudgetExpenseGroup::class);
    }
}
