<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiProviderAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'alias',
        'api_key',
        'base_url',
        'is_active',
        'is_valid',
        'priority',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
        'last_discovery_at',
        'discovery_status',
        'consecutive_failures',
        'cooldown_until',
        'health_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'provider' => AiProvider::class,
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
            'is_valid' => 'boolean',
            'priority' => 'integer',
            'last_tested_at' => 'datetime',
            'last_discovery_at' => 'datetime',
            'consecutive_failures' => 'integer',
            'cooldown_until' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function models(): HasMany
    {
        return $this->hasMany(AiModelCatalog::class, 'account_id');
    }

    /** Suffix 4 char untuk UI tanpa membocorkan key. */
    public function keySuffix(): string
    {
        try {
            $key = (string) ($this->getAttributes()['api_key'] ?? '');
            // api_key tersimpan terenkripsi; gunakan decrypt via cast dengan aman.
            $plain = (string) ($this->api_key ?? '');
        } catch (\Throwable) {
            return '••••';
        }
        if (strlen($plain) < 4) {
            return $plain === '' ? 'belum-diisi' : '••••';
        }

        return substr($plain, -4);
    }

    public function isInCooldown(): bool
    {
        return $this->cooldown_until !== null && $this->cooldown_until->isFuture();
    }

    /** Representasi aman untuk frontend/API (tanpa secret). */
    public function toSafeArray(): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider instanceof AiProvider ? $this->provider->value : (string) $this->provider,
            'alias' => $this->alias,
            'base_url' => $this->base_url,
            'is_active' => (bool) $this->is_active,
            'is_valid' => (bool) $this->is_valid,
            'priority' => (int) $this->priority,
            'health_status' => (string) ($this->health_status ?? 'unknown'),
            'key_suffix' => $this->keySuffix(),
            'key_configured' => blank($this->api_key) ? false : true,
            'last_tested_at' => $this->last_tested_at?->toIso8601String(),
            'last_test_status' => $this->last_test_status,
            'last_test_message' => $this->last_test_message,
            'last_discovery_at' => $this->last_discovery_at?->toIso8601String(),
            'discovery_status' => $this->discovery_status,
            'consecutive_failures' => (int) $this->consecutive_failures,
            'cooldown_until' => $this->cooldown_until?->toIso8601String(),
            'in_cooldown' => $this->isInCooldown(),
            'model_count' => $this->relationLoaded('models') ? $this->models->count() : $this->models()->count(),
        ];
    }
}
