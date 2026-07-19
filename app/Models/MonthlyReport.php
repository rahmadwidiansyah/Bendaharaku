<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyReport extends Model
{
    protected $fillable = [
        'user_id',
        'period_month',
        'title',
        'local_summary',
        'ai_summary',
        'final_summary',
        'metrics',
        'previous_month_summary',
        'comparison_metrics',
        'provider',
        'model',
        'status',
    ];

    protected $casts = [
        'period_month' => 'date',
        'metrics' => 'array',
        'comparison_metrics' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
