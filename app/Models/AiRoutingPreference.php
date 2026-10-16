<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRoutingPreference extends Model
{
    protected $fillable = [
        'user_id',
        'mode',
        'primary_provider',
        'primary_model',
        'fallback_models',
        'provider_priority',
        'free_policy',
        'allow_paid_fallback',
        'max_candidates',
        'max_attempts',
        'expand_beyond_primary',
    ];

    protected function casts(): array
    {
        return [
            'fallback_models' => 'array',
            'provider_priority' => 'array',
            'allow_paid_fallback' => 'boolean',
            'max_candidates' => 'integer',
            'max_attempts' => 'integer',
            'expand_beyond_primary' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function defaultProviderPriority(): array
    {
        return ['gemini', 'openai', 'deepseek', 'openai-compatible'];
    }

    public function providerPriority(): array
    {
        $list = $this->provider_priority ?? self::defaultProviderPriority();
        if (! is_array($list) || $list === []) {
            return self::defaultProviderPriority();
        }

        return array_values(array_unique(array_map('strval', $list)));
    }

    /** @return list<array{provider:string,model:string,account_id?:int}> */
    public function fallbackList(): array
    {
        $raw = $this->fallback_models ?? [];
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $provider = trim((string) ($item['provider'] ?? ''));
            $model = trim((string) ($item['model'] ?? ''));
            if ($provider === '' || $model === '') {
                continue;
            }
            $entry = ['provider' => $provider, 'model' => $model];
            if (isset($item['account_id']) && (int) $item['account_id'] > 0) {
                $entry['account_id'] = (int) $item['account_id'];
            }
            $out[] = $entry;
        }

        return $out;
    }
}
