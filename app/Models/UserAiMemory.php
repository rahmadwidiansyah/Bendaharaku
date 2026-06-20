<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiMemory extends Model
{
    protected $fillable = [
        'user_id',
        'keyword_pattern',
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
}