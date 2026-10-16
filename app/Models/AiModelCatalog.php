<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiModelCatalog extends Model
{
    protected $table = 'ai_model_catalog';

    protected $fillable = [
        'account_id',
        'user_id',
        'provider',
        'model_id',
        'display_name',
        'supports_generate',
        'category',
        'free_tier',
        'free_tier_source',
        'input_token_limit',
        'output_token_limit',
        'is_eligible',
        'is_enabled',
        'discovery_source',
        'last_seen_at',
        'last_validated_at',
        'failure_count',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'provider' => AiProvider::class,
            'supports_generate' => 'boolean',
            'input_token_limit' => 'integer',
            'output_token_limit' => 'integer',
            'is_eligible' => 'boolean',
            'is_enabled' => 'boolean',
            'last_seen_at' => 'datetime',
            'last_validated_at' => 'datetime',
            'failure_count' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AiProviderAccount::class, 'account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toSafeArray(): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'provider' => $this->provider instanceof AiProvider ? $this->provider->value : (string) $this->provider,
            'model_id' => $this->model_id,
            'display_name' => $this->display_name ?? $this->model_id,
            'supports_generate' => (bool) $this->supports_generate,
            'category' => (string) $this->category,
            'free_tier' => (string) $this->free_tier,
            'free_tier_source' => (string) $this->free_tier_source,
            'input_token_limit' => $this->input_token_limit,
            'output_token_limit' => $this->output_token_limit,
            'is_eligible' => (bool) $this->is_eligible,
            'is_enabled' => (bool) $this->is_enabled,
            'discovery_source' => (string) ($this->discovery_source ?? 'live'),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'last_validated_at' => $this->last_validated_at?->toIso8601String(),
            'failure_count' => (int) $this->failure_count,
            'last_error' => $this->last_error,
        ];
    }
}
