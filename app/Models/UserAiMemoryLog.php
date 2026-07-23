<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiMemoryLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'memory_id',
        'user_id',
        'action',
        'transaction_id',
        'source',
        'raw_subject',
        'normalized_subject',
        'memory_keyword',
        'old_weight',
        'new_weight',
        'old_hit_count',
        'new_hit_count',
        'reason',
        'metadata',
        'algorithm_version',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_weight' => 'float',
            'new_weight' => 'float',
            'old_hit_count' => 'integer',
            'new_hit_count' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
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
