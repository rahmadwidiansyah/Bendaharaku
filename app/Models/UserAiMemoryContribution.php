<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiMemoryContribution extends Model
{
    protected $fillable = [
        'memory_id',
        'user_id',
        'transaction_id',
        'source',
        'keyword',
        'target_type',
        'target_id',
        'target_name',
        'weight_delta',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight_delta' => 'float',
            'is_active' => 'boolean',
            'target_id' => 'integer',
        ];
    }

    public function memory(): BelongsTo
    {
        return $this->belongsTo(UserAiMemory::class, 'memory_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
