<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiDailyMetric extends Model
{
    protected $fillable = [
        'user_id', 'date', 'provider',
        'total_requests', 'total_success', 'total_drafts', 'total_corrections',
        'avg_raw_confidence', 'avg_final_confidence',
        'prompt_tokens', 'completion_tokens', 'total_tokens', 'estimated_cost_usd',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'avg_raw_confidence' => 'float',
            'avg_final_confidence' => 'float',
            'estimated_cost_usd' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
