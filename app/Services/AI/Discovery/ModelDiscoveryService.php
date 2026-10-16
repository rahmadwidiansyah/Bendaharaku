<?php

declare(strict_types=1);

namespace App\Services\AI\Discovery;

use App\Enums\AiProvider;
use App\Models\AiModelCatalog;
use App\Models\AiProviderAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Discovery model per akun (per-key scoping).
 * - Gemini: GET /v1beta/models (resmi, paginated, defensif).
 * - OpenAI/DeepSeek: GET /v1/models atau /models dengan Bearer.
 * - OpenAI-compatible: best-effort ke {base}/models; jika gagal → manual.
 * Kegagalan discovery TIDAK menghapus katalog terakhir yang valid.
 */
class ModelDiscoveryService
{
    public const CACHE_TTL_SECONDS = 21600; // 6 jam ala GAEClip

    public const MAX_PAGES = 10;

    public function discoverForAccount(AiProviderAccount $account, bool $forceRefresh = false, int $timeout = 20): array
    {
        $provider = $account->provider instanceof AiProvider
            ? $account->provider
            : AiProvider::tryFrom((string) $account->provider);

        if (! $provider) {
            return $this->result($account, 'error', [], 'Provider tidak dikenal.');
        }

        // Hormati cache TTL kecuali force.
        if (! $forceRefresh && $account->last_discovery_at) {
            $age = now()->diffInSeconds($account->last_discovery_at);
            if ($age < self::CACHE_TTL_SECONDS && $account->discovery_status === 'live') {
                $cached = $this->loadCatalog($account);

                return $this->result($account, 'cache', $cached, null, true);
            }
        }

        try {
            $normalized = match ($provider) {
                AiProvider::Gemini => $this->discoverGemini($account, $timeout),
                AiProvider::OpenAI => $this->discoverOpenAiStyle($account, 'https://api.openai.com/v1/models', $timeout),
                AiProvider::DeepSeek => $this->discoverOpenAiStyle($account, 'https://api.deepseek.com/models', $timeout),
                AiProvider::OpenAiCompatible => $this->discoverCompatible($account, $timeout),
            };

            $this->persistCatalog($account, $normalized, 'live');
            $account->update([
                'last_discovery_at' => now(),
                'discovery_status' => 'live',
            ]);

            Log::info('ModelDiscovery: success', [
                'user_id' => $account->user_id,
                'provider' => $provider->value,
                'account_id' => $account->id,
                'discovered' => count($normalized),
                'eligible' => count(array_filter($normalized, fn ($m) => $m['eligible'] ?? false)),
            ]);

            return $this->result($account, 'live', $this->loadCatalog($account), null);
        } catch (DiscoveryAuthException $e) {
            // Key invalid: tandai akun, tapi pertahankan katalog lama.
            $account->update([
                'is_valid' => false,
                'health_status' => 'invalid',
                'discovery_status' => 'error',
                'last_test_status' => 'auth_error',
                'last_test_message' => 'API key tidak valid atau revoked.',
            ]);
            $cached = $this->loadCatalog($account);

            return $this->result($account, $cached === [] ? 'error' : 'cache-stale', $cached, 'Autentikasi gagal: API key tidak valid.');
        } catch (\Throwable $e) {
            Log::warning('ModelDiscovery: failed, keep last-known-good', [
                'user_id' => $account->user_id,
                'provider' => $provider->value,
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            $cached = $this->loadCatalog($account);
            $account->update(['discovery_status' => $cached === [] ? 'error' : 'cache-stale']);

            return $this->result($account, $cached === [] ? 'error' : 'cache-stale', $cached, 'Discovery gagal: '.$e->getMessage());
        }
    }

    /** @return list<array> normalized */
    private function discoverGemini(AiProviderAccount $account, int $timeout): array
    {
        $apiKey = (string) $account->api_key;
        if (blank($apiKey)) {
            throw new DiscoveryAuthException('API key kosong.');
        }
        $models = [];
        $pageToken = null;
        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $query = ['pageSize' => '100'];
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }
            try {
                $response = Http::timeout($timeout)->withoutRedirecting()
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->get('https://generativelanguage.googleapis.com/v1beta/models', $query);
            } catch (ConnectionException $e) {
                throw new \RuntimeException('Gemini models API tidak terjangkau (timeout/jaringan).');
            }
            $status = $response->status();
            if (in_array($status, [401, 403], true)) {
                throw new DiscoveryAuthException('Gemini authentication failed.');
            }
            if ($status === 404) {
                throw new \RuntimeException('Gemini models endpoint tidak ditemukan (404).');
            }
            if (in_array($status, [408, 429, 500, 502, 503, 504], true)) {
                throw new \RuntimeException("Gemini models API transient error ({$status}).");
            }
            if (! $response->successful()) {
                throw new \RuntimeException("Gemini models API error ({$status}).");
            }
            $data = $response->json() ?? [];
            $batch = $data['models'] ?? [];
            if (! is_array($batch)) {
                throw new \RuntimeException('Respons models API tidak valid.');
            }
            foreach ($batch as $raw) {
                if (is_array($raw)) {
                    $models[] = ModelNormalizer::normalizeGemini($raw);
                }
            }
            $pageToken = $data['nextPageToken'] ?? null;
            if (! $pageToken) {
                break;
            }
        }

        return $models;
    }

    /** @return list<array> */
    private function discoverOpenAiStyle(AiProviderAccount $account, string $url, int $timeout): array
    {
        $apiKey = (string) $account->api_key;
        if (blank($apiKey)) {
            throw new DiscoveryAuthException('API key kosong.');
        }
        try {
            $response = Http::timeout($timeout)->withoutRedirecting()->withToken($apiKey)->get($url);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('Models API tidak terjangkau.');
        }
        $status = $response->status();
        if (in_array($status, [401, 403], true)) {
            throw new DiscoveryAuthException('Authentication failed.');
        }
        if (! $response->successful()) {
            throw new \RuntimeException("Models API error ({$status}).");
        }
        $data = $response->json() ?? [];
        $list = $data['data'] ?? [];
        if (! is_array($list)) {
            throw new \RuntimeException('Respons models API tidak valid.');
        }
        $provider = $account->provider instanceof AiProvider ? $account->provider : AiProvider::OpenAI;
        $out = [];
        foreach ($list as $item) {
            $id = is_array($item) ? trim((string) ($item['id'] ?? '')) : '';
            if ($id === '') {
                continue;
            }
            $out[] = ModelNormalizer::normalizeOpenAiCompatible($id, $provider);
        }

        return $out;
    }

    /** @return list<array> */
    private function discoverCompatible(AiProviderAccount $account, int $timeout): array
    {
        // Best-effort: turunkan {base}/chat/completions → {base}/models.
        $base = trim((string) ($account->base_url ?: config('bendaharaku.ai.openai_compatible.base_url')));
        if (blank($base)) {
            throw new \RuntimeException('Base URL OpenAI-compatible belum dikonfigurasi (gunakan konfigurasi manual).');
        }
        try {
            $base = \App\Services\AI\Security\SafeEndpointValidator::normalizeBaseUrl($base);
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException('Base URL tidak aman: '.$e->getMessage());
        }
        $url = \App\Services\AI\Security\SafeEndpointValidator::deriveModelsUrl($base);
        $apiKey = (string) $account->api_key;
        if (blank($apiKey)) {
            $apiKey = (string) config('bendaharaku.ai.openai_compatible.api_key');
        }
        if (blank($apiKey)) {
            throw new DiscoveryAuthException('API key kosong.');
        }
        $lastError = null;
        foreach ([$url] as $url) {
            try {
                // Fail-closed terhadap redirect (mencegah SSRF via 302 ke metadata/internal).
                $response = Http::timeout($timeout)->withoutRedirecting()->withToken($apiKey)->get($url);
            } catch (ConnectionException $e) {
                $lastError = 'Models API tidak terjangkau.';

                continue;
            }
            if ($response->redirect()) {
                $lastError = 'Endpoint me-redirect (ditolak demi keamanan); gunakan URL final langsung.';

                continue;
            }
            $status = $response->status();
            if (in_array($status, [401, 403], true)) {
                throw new DiscoveryAuthException('Authentication failed.');
            }
            if (! $response->successful()) {
                $lastError = "Models API error ({$status}) — provider ini mungkin tidak mendukung listing; gunakan konfigurasi manual.";

                continue;
            }
            $data = $response->json() ?? [];
            $list = $data['data'] ?? [];
            if (! is_array($list)) {
                $lastError = 'Respons models API tidak valid; gunakan konfigurasi manual.';

                continue;
            }
            $out = [];
            foreach ($list as $item) {
                $id = is_array($item) ? trim((string) ($item['id'] ?? '')) : '';
                if ($id === '') {
                    continue;
                }
                $out[] = ModelNormalizer::normalizeOpenAiCompatible($id, AiProvider::OpenAiCompatible);
            }

            return $out;
        }
        throw new \RuntimeException($lastError ?? 'Discovery tidak didukung provider ini; gunakan konfigurasi manual.');
    }

    /** Simpan katalog: upsert per (account, model); yang hilang dari discovery ditandai, bukan dihapus. */
    private function persistCatalog(AiProviderAccount $account, array $normalized, string $source): void
    {
        $providerValue = $account->provider instanceof AiProvider ? $account->provider->value : (string) $account->provider;
        $seenIds = [];
        foreach ($normalized as $info) {
            $modelId = trim((string) ($info['id'] ?? ''));
            if ($modelId === '') {
                continue;
            }
            $seenIds[] = $modelId;
            AiModelCatalog::updateOrCreate(
                ['account_id' => $account->id, 'model_id' => $modelId],
                [
                    'user_id' => $account->user_id,
                    'provider' => $providerValue,
                    'display_name' => $info['display_name'] ?? $modelId,
                    'supports_generate' => (bool) ($info['supports_generate'] ?? false),
                    'category' => (string) ($info['category'] ?? 'other'),
                    'free_tier' => (string) ($info['free_tier'] ?? 'unknown'),
                    'free_tier_source' => (string) ($info['free_tier_source'] ?? 'local_policy'),
                    'input_token_limit' => $info['input_token_limit'] ?? null,
                    'output_token_limit' => $info['output_token_limit'] ?? null,
                    'is_eligible' => (bool) ($info['eligible'] ?? false),
                    'discovery_source' => 'live',
                    'last_seen_at' => now(),
                    'last_error' => null,
                ]
            );
        }
        // Model yang hilang dari discovery: naikkan failure_count + tandai, jangan hapus preferensi user.
        if ($seenIds !== []) {
            AiModelCatalog::where('account_id', $account->id)
                ->whereNotIn('model_id', $seenIds)
                ->update([
                    'failure_count' => \DB::raw('failure_count + 1'),
                    'last_error' => 'Tidak muncul pada discovery terakhir (ditandai, tidak dihapus).',
                    'updated_at' => now(),
                ]);
        }
    }

    /** @return list<array> safe */
    private function loadCatalog(AiProviderAccount $account): array
    {
        return $account->models()->orderBy('model_id')->get()->map->toSafeArray()->all();
    }

    private function result(AiProviderAccount $account, string $source, array $models, ?string $error, bool $fromCache = false): array
    {
        $eligible = count(array_filter($models, fn ($m) => ($m['is_eligible'] ?? false) && ($m['supports_generate'] ?? false)));

        return [
            'account_id' => $account->id,
            'provider' => $account->provider instanceof AiProvider ? $account->provider->value : (string) $account->provider,
            'source' => $source,
            'from_cache' => $fromCache,
            'discovered' => count($models),
            'eligible' => $eligible,
            'models' => $models,
            'error' => $error,
            'discovered_at' => now()->toIso8601String(),
        ];
    }
}

class DiscoveryAuthException extends \RuntimeException {}
