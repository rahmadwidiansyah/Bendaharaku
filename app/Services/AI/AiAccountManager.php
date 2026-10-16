<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\AiProvider;
use App\Models\AiProviderAccount;
use App\Models\AiRoutingPreference;
use App\Models\User;
use App\Services\AI\Adapters\DeepSeekAdapter;
use App\Services\AI\Adapters\GeminiAdapter;
use App\Services\AI\Adapters\OpenAIAdapter;
use App\Services\AI\Adapters\OpenAiCompatibleAdapter;
use App\Services\AI\Discovery\ModelDiscoveryService;
use Illuminate\Support\Facades\Log;

/**
 * CRUD akun API + uji koneksi nyata + refresh discovery.
 * Tidak pernah mengembalikan secret utuh ke frontend (gunakan toSafeArray).
 */
class AiAccountManager
{
    public function __construct(
        private ModelDiscoveryService $discovery,
        private GeminiAdapter $gemini,
        private OpenAIAdapter $openai,
        private DeepSeekAdapter $deepseek,
        private OpenAiCompatibleAdapter $compatible,
    ) {}

    /** @return list<array> safe */
    public function listFor(User $user): array
    {
        $accounts = AiProviderAccount::where('user_id', $user->id)
            ->withCount('models')
            ->orderBy('provider')
            ->orderBy('priority')
            ->get();

        // Tambahkan akun virtual dari .env jika user belum punya akun compatible aktif.
        $hasCompatible = $accounts->firstWhere(fn ($a) => ((string) ($a->provider->value ?? $a->provider)) === 'openai-compatible');
        $envKey = (string) config('bendaharaku.ai.openai_compatible.api_key');
        $envUrl = (string) config('bendaharaku.ai.openai_compatible.base_url');
        $virtual = null;
        if (! $hasCompatible && ! blank($envKey) && ! blank($envUrl)) {
            $virtual = [
                'id' => 0,
                'provider' => 'openai-compatible',
                'alias' => 'Default (.env)',
                'base_url' => $envUrl,
                'is_active' => true,
                'is_valid' => true,
                'priority' => 99,
                'health_status' => 'unknown',
                'key_suffix' => '••••',
                'key_configured' => true,
                'virtual' => true,
                'last_test_status' => null,
                'last_discovery_at' => null,
                'discovery_status' => 'manual',
                'consecutive_failures' => 0,
                'in_cooldown' => false,
                'model_count' => 1,
            ];
        }

        $list = $accounts->map->toSafeArray()->all();
        if ($virtual) {
            $list[] = $virtual;
        }

        return $list;
    }

    public function create(User $user, AiProvider $provider, string $alias, string $apiKey, ?string $baseUrl = null, int $priority = 1): AiProviderAccount
    {
        $alias = trim($alias) === '' ? 'Utama' : mb_substr(trim($alias), 0, 100);
        // Defense in depth: normalisasi + tolak SSRF walau validasi request lolos/dilewati.
        $safeBaseUrl = null;
        if ($provider === AiProvider::OpenAiCompatible && is_string($baseUrl) && trim($baseUrl) !== '') {
            $safeBaseUrl = \App\Services\AI\Security\SafeEndpointValidator::normalizeBaseUrl($baseUrl);
        }
        $account = AiProviderAccount::create([
            'user_id' => $user->id,
            'provider' => $provider->value,
            'alias' => $alias,
            'api_key' => $apiKey,
            'base_url' => $safeBaseUrl,
            'is_active' => true,
            'is_valid' => true,
            'priority' => max(1, $priority),
            'health_status' => 'unknown',
            'discovery_status' => 'manual',
        ]);

        // Discovery awal (best-effort, tidak gagalkan pembuatan akun).
        try {
            $this->discovery->discoverForAccount($account->fresh(), true);
        } catch (\Throwable $e) {
            Log::warning('AiAccountManager: initial discovery failed', ['account_id' => $account->id, 'error' => $e->getMessage()]);
        }

        return $account->fresh();
    }

    public function updateAlias(AiProviderAccount $account, string $alias): AiProviderAccount
    {
        $account->update(['alias' => mb_substr(trim($alias) === '' ? $account->alias : trim($alias), 0, 100)]);

        return $account->fresh();
    }

    public function replaceKey(AiProviderAccount $account, string $apiKey, ?string $baseUrl = null): AiProviderAccount
    {
        $patch = [
            'api_key' => $apiKey,
            'is_valid' => true,
            'health_status' => 'unknown',
            'consecutive_failures' => 0,
            'cooldown_until' => null,
            'last_test_status' => null,
        ];
        if ($account->provider === AiProvider::OpenAiCompatible && $baseUrl !== null) {
            $patch['base_url'] = trim($baseUrl) === ''
                ? null
                : \App\Services\AI\Security\SafeEndpointValidator::normalizeBaseUrl($baseUrl);
        }
        $account->update($patch);

        try {
            $this->discovery->discoverForAccount($account->fresh(), true);
        } catch (\Throwable $e) {
            Log::warning('AiAccountManager: discovery after key replace failed', ['account_id' => $account->id]);
        }

        return $account->fresh();
    }

    public function setActive(AiProviderAccount $account, bool $active): AiProviderAccount
    {
        $account->update(['is_active' => $active]);
        if (! $active) {
            $account->update(['health_status' => 'disabled']);
        } elseif ($account->health_status === 'disabled') {
            $account->update(['health_status' => 'unknown']);
        }

        return $account->fresh();
    }

    public function delete(User $user, AiProviderAccount $account): void
    {
        // Lindungi akun terakhir yang aktif agar user tidak terkunci total
        // (kecuali masih ada default .env sebagai fallback).
        $remaining = AiProviderAccount::where('user_id', $user->id)->where('id', '!=', $account->id)->count();
        $envKey = (string) config('bendaharaku.ai.openai_compatible.api_key');
        if ($remaining === 0 && blank($envKey)) {
            throw new \RuntimeException('Tidak dapat menghapus satu-satunya akun AI. Tambahkan akun lain terlebih dahulu.');
        }
        $account->delete();
    }

    /**
     * Uji koneksi NYATA (bukan placeholder): panggil generateText ringan.
     *
     * @return array{ok:bool,model:string,latency_ms:?int,error_code:?string,message:string}
     */
    public function testConnection(AiProviderAccount $account, ?string $model = null): array
    {
        $provider = $account->provider instanceof AiProvider ? $account->provider : AiProvider::tryFrom((string) $account->provider);
        if (! $provider) {
            return ['ok' => false, 'model' => '', 'latency_ms' => null, 'error_code' => 'unknown_provider', 'message' => 'Provider tidak dikenal.'];
        }
        $apiKey = (string) $account->api_key;
        if ($account->id === 0) {
            $apiKey = (string) config('bendaharaku.ai.openai_compatible.api_key');
        }
        if (blank($apiKey)) {
            return ['ok' => false, 'model' => '', 'latency_ms' => null, 'error_code' => 'missing_api_key', 'message' => 'API key belum diisi.'];
        }

        // Pilih model uji: preferensi eksplisit → katalog eligible pertama → default provider.
        $testModel = $model;
        if (blank($testModel) && $account->id > 0) {
            $testModel = $account->models()->where('is_eligible', true)->where('is_enabled', true)->orderBy('model_id')->value('model_id');
        }
        if (blank($testModel)) {
            $testModel = $provider->defaultModel();
            if ($provider === AiProvider::OpenAiCompatible && blank($testModel)) {
                $testModel = 'gpt-4o-mini';
            }
        }

        $adapter = match ($provider) {
            AiProvider::Gemini => $this->gemini,
            AiProvider::OpenAI => $this->openai,
            AiProvider::DeepSeek => $this->deepseek,
            AiProvider::OpenAiCompatible => $this->compatible,
        };

        $started = microtime(true);
        try {
            // Jika akun compatible punya base_url kustom, set sementara via config override.
            if ($provider === AiProvider::OpenAiCompatible && $account->base_url) {
                config()->set('bendaharaku.ai.openai_compatible.base_url', $account->base_url);
            }
            $text = $adapter->generateText('Reply with exactly: ok', $apiKey, (string) $testModel);
            $latency = (int) round((microtime(true) - $started) * 1000);
            if (blank($text)) {
                throw new \RuntimeException('Respons kosong dari provider.');
            }
            if ($account->id > 0) {
                $account->update([
                    'is_valid' => true,
                    'health_status' => 'healthy',
                    'consecutive_failures' => 0,
                    'cooldown_until' => null,
                    'last_tested_at' => now(),
                    'last_test_status' => 'ok',
                    'last_test_message' => 'Koneksi berhasil.',
                ]);
            }

            return ['ok' => true, 'model' => (string) $testModel, 'latency_ms' => $latency, 'error_code' => null, 'message' => 'Koneksi berhasil.'];
        } catch (\Throwable $e) {
            $latency = (int) round((microtime(true) - $started) * 1000);
            $lower = strtolower($e->getMessage());
            $code = 'unknown_error';
            $message = 'Koneksi gagal.';
            if (str_contains($lower, 'api key tidak valid') || str_contains($lower, '401') || str_contains($lower, '403')) {
                $code = 'invalid_api_key';
                $message = 'API key tidak valid atau akses ditolak.';
                if ($account->id > 0) {
                    $account->update(['is_valid' => false, 'health_status' => 'invalid', 'last_tested_at' => now(), 'last_test_status' => 'auth_error', 'last_test_message' => $message]);
                }
            } elseif (str_contains($lower, 'rate') || str_contains($lower, 'quota') || str_contains($lower, '429')) {
                $code = 'rate_limit';
                $message = 'Rate limit/kuota tercapai saat pengujian.';
                if ($account->id > 0) {
                    $account->update(['health_status' => 'rate_limited', 'last_tested_at' => now(), 'last_test_status' => 'transient', 'last_test_message' => $message]);
                }
            } elseif (str_contains($lower, 'timeout') || str_contains($lower, 'timed out')) {
                $code = 'timeout';
                $message = 'Permintaan pengujian timeout.';
            } else {
                if ($account->id > 0) {
                    $account->update(['last_tested_at' => now(), 'last_test_status' => 'transient', 'last_test_message' => mb_substr($e->getMessage(), 0, 300)]);
                }
                $message = 'Koneksi gagal: '.mb_substr($e->getMessage(), 0, 200);
            }

            return ['ok' => false, 'model' => (string) $testModel, 'latency_ms' => $latency, 'error_code' => $code, 'message' => $message];
        }
    }

    public function refreshDiscovery(AiProviderAccount $account): array
    {
        return $this->discovery->discoverForAccount($account, true);
    }

    public function getOrCreateRouting(User $user): AiRoutingPreference
    {
        return AiRoutingPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'mode' => 'automatic',
                'provider_priority' => AiRoutingPreference::defaultProviderPriority(),
                'free_policy' => 'free_first',
                'allow_paid_fallback' => false,
                'max_candidates' => 4,
                'max_attempts' => 8,
                'expand_beyond_primary' => true,
            ]
        );
    }
}
