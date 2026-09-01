<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserAiMemory extends Model
{
    protected $fillable = [
        'user_id',
        'keyword_pattern',
        'raw_subject',
        'normalized_subject',
        'memory_keyword',
        'target_type',
        'category_id',
        'wallet_id',
        'hit_count',
        'weight',
        'last_applied_at',
    ];

    protected function casts(): array
    {
        return [
            'hit_count' => 'integer',
            'weight' => 'float',
            'last_applied_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(UserAiMemoryContribution::class, 'memory_id');
    }

    public function activeContributions(): HasMany
    {
        return $this->contributions()->where('is_active', true);
    }
}
