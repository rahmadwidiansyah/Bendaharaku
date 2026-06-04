<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiPreference extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'selected_model',
        'is_active_provider',
    ];

    protected function casts(): array
    {
        return [
            'provider' => AiProvider::class,
            'is_active_provider' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
