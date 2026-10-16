<?php

declare(strict_types=1);

namespace App\Services\AI\Routing;

use App\Models\AiModelCatalog;
use App\Models\AiProviderAccount;
use Illuminate\Support\Facades\Log;

/**
 * Pelacak kesehatan per akun + per model (cooldown, bukan circuit breaker penuh).
 * Status health: healthy|degraded|rate_limited|unavailable|invalid|disabled|unknown.
 */
class ProviderHealthTracker
{
    public function isUsable(AiProviderAccount $account): bool
    {
        if (! $account->is_active || ! $account->is_valid) {
            return false;
        }
        if (in_array($account->health_status, ['invalid', 'disabled'], true)) {
            return false;
        }
        if ($account->isInCooldown()) {
            return false;
        }

        return true;
    }

    public function recordSuccess(AiProviderAccount $account, ?AiModelCatalog $model = null): void
    {
        $account->update([
            'consecutive_failures' => 0,
            'cooldown_until' => null,
            'health_status' => 'healthy',
            'last_test_status' => 'ok',
        ]);
        if ($model) {
            $model->update([
                'failure_count' => 0,
                'last_error' => null,
                'last_validated_at' => now(),
            ]);
        }
    }

    /** @param array{category:string,retry_after_s:?float} $classification */
    public function recordFailure(AiProviderAccount $account, ?AiModelCatalog $model, array $classification, string $rawMessage = ''): void
    {
        $category = (string) ($classification['category'] ?? AiErrorClassifier::CAT_UNKNOWN);
        $retryAfter = $classification['retry_after_s'] ?? null;

        $failures = (int) $account->consecutive_failures + 1;
        $patch = ['consecutive_failures' => $failures];

        switch ($category) {
            case AiErrorClassifier::CAT_AUTH:
                $patch['is_valid'] = false;
                $patch['health_status'] = 'invalid';
                $patch['last_test_status'] = 'auth_error';
                $patch['last_test_message'] = 'API key tidak valid atau revoked (otomatis ditandai).';
                break;
            case AiErrorClassifier::CAT_QUOTA:
            case AiErrorClassifier::CAT_RATE_LIMIT_ACCOUNT:
            case AiErrorClassifier::CAT_RATE_LIMIT_MODEL:
                $patch['health_status'] = 'rate_limited';
                $patch['cooldown_until'] = now()->addSeconds((int) ($retryAfter ?? 60));
                break;
            case AiErrorClassifier::CAT_SERVER:
                $patch['health_status'] = $failures >= 3 ? 'unavailable' : 'degraded';
                $patch['cooldown_until'] = now()->addSeconds((int) ($retryAfter ?? 30));
                break;
            case AiErrorClassifier::CAT_TIMEOUT:
            case AiErrorClassifier::CAT_NETWORK:
                $patch['health_status'] = $failures >= 3 ? 'degraded' : ($account->health_status ?? 'unknown');
                $patch['cooldown_until'] = now()->addSeconds(15);
                break;
            case AiErrorClassifier::CAT_MODEL_NOT_FOUND:
                $patch['health_status'] = $account->health_status ?? 'unknown';
                break;
            default:
                $patch['health_status'] = $failures >= 5 ? 'degraded' : ($account->health_status ?? 'unknown');
                break;
        }

        $account->update($patch);

        if ($model) {
            $modelPatch = ['failure_count' => (int) $model->failure_count + 1];
            if (in_array($category, [AiErrorClassifier::CAT_MODEL_NOT_FOUND, AiErrorClassifier::CAT_CONTEXT_LIMIT], true)) {
                // Model terbukti tak cocok → nonaktifkan agar tak dipilih terus.
                $modelPatch['is_enabled'] = false;
                $modelPatch['last_error'] = $category === AiErrorClassifier::CAT_MODEL_NOT_FOUND
                    ? 'Model tidak ditemukan / tidak mendukung generate (otomatis dinonaktifkan).'
                    : 'Model menolak konteks (otomatis dinonaktifkan untuk request serupa).';
            } else {
                $modelPatch['last_error'] = mb_substr($rawMessage !== '' ? $rawMessage : $category, 0, 500);
            }
            $model->update($modelPatch);
        }

        Log::warning('AI health: failure recorded', [
            'user_id' => $account->user_id,
            'provider' => (string) $account->provider->value ?? (string) $account->provider,
            'account_id' => $account->id,
            'model' => $model?->model_id,
            'category' => $category,
            'failures' => $failures,
        ]);
    }

    public function resetAccount(AiProviderAccount $account): void
    {
        $account->update([
            'consecutive_failures' => 0,
            'cooldown_until' => null,
            'health_status' => 'unknown',
            'is_valid' => true,
        ]);
    }
}
