<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFeedbackLog extends Model
{
    protected $fillable = [
        'parse_log_id',
        'user_id',
        'original_payload',
        'corrected_payload',
        'divergence_score',
    ];

    protected function casts(): array
    {
        return [
            'original_payload' => 'array',
            'corrected_payload' => 'array',
            'divergence_score' => 'float',
        ];
    }

    public function parseLog(): BelongsTo
    {
        // Model AiParseLog diasumsikan sudah ada (sebelumnya)
        return $this->belongsTo(AiParseLog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}