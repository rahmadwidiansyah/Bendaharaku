<?php

declare(strict_types=1);

namespace App\Services\AI\Routing;

use App\DTO\AIParseResult;
use App\DTO\AIParseResultMulti;
use App\Enums\AiProvider;
use App\Exceptions\AiProviderException;
use App\Models\AiModelCatalog;
use App\Models\AiProviderAccount;
use App\Models\AiRoutingPreference;
use App\Models\User;
use App\Services\AI\AiProviderFactory;
use Illuminate\Support\Facades\Log;

/**
 * Eksekutor routing: coba kandidat berurutan dengan batas ketat.
 * - Batas total percobaan per pesan (default 8) + batas waktu total (70s).
 * - Retry same-pair hanya untuk timeout/server/network (1x + backoff+jitter).
 * - NEXT_ACCOUNT/NEXT_PROVIDER skip sisa kandidat akun/provider tsb.
 * - PERMANENT (request invalid) hentikan semua.
 * - Tidak menimbulkan efek samping bisnis (hanya AI generation); caller yang simpan draft/log sekali.
 */
class AiRoutingExecutor
{
    public const TOTAL_BUDGET_SECONDS = 70;

    public function __construct(
        private AiProviderFactory $factory,
        private ProviderHealthTracker $health,
        private ModelResolver $resolver,
    ) {}

    /**
     * Eksekusi parseTransaction lintas kandidat.
     *
     * @param  callable(AiProviderAccount, string $model, AiProvider $provider): AIParseResult  $attempt
     * @return array{result:AIParseResult, routing:array}
     *
     * @throws AiProviderException jika semua kandidat habis
     */
    public function executeParse(User $user, callable $attempt, ?AiRoutingPreference $routing = null, ?callable $sleepFn = null): array
    {
        $sleepFn ??= fn (float $seconds) => usleep((int) round($seconds * 1_000_000));
        $routing ??= AiRoutingPreference::where('user_id', $user->id)->first();
        $maxAttempts = max(1, min(12, (int) ($routing?->max_attempts ?? 8)));

        $resolved = $this->resolver->resolve($user, $routing);
        $candidates = $resolved['candidates'];
        $traceId = (string) \Str::uuid();
        $started = microtime(true);
        $attempts = [];
        $failures = [];
        $skippedAccounts = [];
        $skippedProviders = [];
        $tried = 0;

        if ($candidates === []) {
            throw new AiProviderException('ai-router', 'Tidak ada kandidat model yang layak (semua akun cooldown/invalid atau terfilter kebijakan).');
        }

        foreach ($candidates as $index => $candidate) {
            /** @var AiProviderAccount $account */
            $account = $candidate['account'];
            /** @var AiProvider $provider */
            $provider = $candidate['provider'];
            $modelId = (string) $candidate['model_id'];
            /** @var AiModelCatalog|string|null $catalogOrId */
            $catalogOrId = $candidate['model'];
            $catalog = $catalogOrId instanceof AiModelCatalog ? $catalogOrId : null;
            // Akun virtual id=0 tidak punya baris DB — buat objek katalog virtual untuk health tracking (no-op).
            if ($catalog === null && is_string($catalogOrId)) {
                $catalog = null;
            }

            if (isset($skippedAccounts[$account->id]) || isset($skippedProviders[$provider->value])) {
                $attempts[] = $this->attemptRecord($provider, $account, $modelId, 0, 'SKIPPED', 'dilewati (akun/provider sudah dinyatakan habis)', $index);

                continue;
            }
            if ($tried >= $maxAttempts) {
                break;
            }
            if ((microtime(true) - $started) > self::TOTAL_BUDGET_SECONDS) {
                $failures[] = ['provider' => $provider->value, 'account_id' => $account->id, 'model' => $modelId, 'category' => 'TIMEOUT', 'message' => 'Batas waktu routing total tercapai.'];
                break;
            }

            // Percobaan pertama + maksimal 1 retry same-pair untuk retryable.
            $retriesLeft = 1;
            $attemptNo = 0;
            while (true) {
                if ($tried >= $maxAttempts || (microtime(true) - $started) > self::TOTAL_BUDGET_SECONDS) {
                    break 2;
                }
                $tried++;
                $attemptNo++;
                $t0 = microtime(true);
                try {
                    // Akun virtual: api_key dari .env (AiAccountManager pattern).
                    $result = $attempt($account, $modelId, $provider);
                    $latency = (int) round((microtime(true) - $t0) * 1000);
                    $attempts[] = $this->attemptRecord($provider, $account, $modelId, $latency, 'OK', null, $index, $attemptNo);
                    if ($account->id > 0) {
                        $this->health->recordSuccess($account->fresh(), $catalog?->exists ? $catalog->fresh() : null);
                    }
                    $routingMeta = [
                        'trace_id' => $traceId,
                        'selected_provider' => $provider->value,
                        'selected_account_id' => $account->id,
                        'selected_account_alias' => $account->alias ?? null,
                        'selected_model' => $modelId,
                        'selection_method' => $candidate['source'] ?? 'unknown',
                        'resolver_method' => $resolved['method'] ?? 'unknown',
                        'attempt_count' => $tried,
                        'fallback_from' => $index > 0 ? ($candidates[$index - 1]['provider']->value.'/'.$candidates[$index - 1]['model_id']) : null,
                        'fallback_to' => $provider->value.'/'.$modelId,
                        'fallback_reason' => $index > 0 ? ($failures[count($failures) - 1]['category'] ?? null) : null,
                        'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                        'attempts' => $attempts,
                        'failures' => $failures,
                        'skipped' => $resolved['skipped'] ?? [],
                        'final_status' => 'success',
                    ];

                    return ['result' => $result, 'routing' => $routingMeta];
                } catch (\Throwable $e) {
                    $latency = (int) round((microtime(true) - $t0) * 1000);
                    $classified = AiErrorClassifier::classify($e);
                    $category = $classified['category'];
                    $action = $classified['action'];
                    $attempts[] = $this->attemptRecord($provider, $account, $modelId, $latency, $category, $classified['safe_message'], $index, $attemptNo);
                    $failures[] = ['provider' => $provider->value, 'account_id' => $account->id, 'model' => $modelId, 'category' => $category, 'action' => $action, 'message' => $classified['safe_message']];
                    if ($account->id > 0) {
                        $this->health->recordFailure($account->fresh(), $catalog?->exists ? $catalog->fresh() : null, $classified, $e->getMessage());
                    }

                    Log::warning('AI routing: attempt failed', [
                        'trace_id' => $traceId,
                        'user_id' => $user->id,
                        'provider' => $provider->value,
                        'account_id' => $account->id,
                        'model' => $modelId,
                        'category' => $category,
                        'action' => $action,
                        'attempt' => $attemptNo,
                    ]);

                    if ($action === AiErrorClassifier::ACTION_PERMANENT) {
                        // Request invalid: hentikan semua, jangan habiskan kandidat.
                        throw new AiProviderException($provider->value, $classified['safe_message'].' (tidak dicoba ke kandidat lain).');
                    }
                    if ($action === AiErrorClassifier::ACTION_NEXT_PROVIDER) {
                        $skippedProviders[$provider->value] = true;
                        break;
                    }
                    if ($action === AiErrorClassifier::ACTION_NEXT_ACCOUNT) {
                        $skippedAccounts[$account->id] = true;
                        break;
                    }
                    if ($action === AiErrorClassifier::ACTION_RETRY_SAME && $classified['retryable'] && $retriesLeft > 0) {
                        $retriesLeft--;
                        $delay = $this->backoffSeconds($attemptNo, $classified['retry_after_s']);
                        $sleepFn($delay);

                        continue;
                    }
                    // NEXT_MODEL atau retry habis → kandidat berikutnya.
                    break;
                }
            }
        }

        $summary = implode('; ', array_map(fn ($f) => ($f['provider'] ?? '?').'/'.($f['model'] ?? '?').':'.$f['category'], array_slice($failures, 0, 5)));
        Log::error('AI routing: exhausted', ['trace_id' => $traceId, 'user_id' => $user->id, 'attempts' => $tried, 'failures' => $failures]);

        throw new AiProviderException('ai-router', 'Semua kandidat AI gagal ('.$tried.' percobaan'.($summary !== '' ? ': '.$summary : '').').');
    }

    /**
     * Eksekusi parseMultiTransaction lintas kandidat (dipakai MultiTransactionProcessor).
     *
     * @param  callable(AiProviderAccount, string $model, AiProvider $provider): AIParseResultMulti  $attempt
     * @return array{result:AIParseResultMulti, routing:array}
     *
     * @throws AiProviderException jika semua kandidat habis
     */
    public function executeMulti(User $user, callable $attempt, ?AiRoutingPreference $routing = null, ?callable $sleepFn = null): array
    {
        $sleepFn ??= fn (float $seconds) => usleep((int) round($seconds * 1_000_000));
        $routing ??= AiRoutingPreference::where('user_id', $user->id)->first();
        $maxAttempts = max(1, min(12, (int) ($routing?->max_attempts ?? 8)));

        $resolved = $this->resolver->resolve($user, $routing);
        $candidates = $resolved['candidates'];
        $traceId = (string) \Str::uuid();
        $started = microtime(true);
        $attempts = [];
        $failures = [];
        $skippedAccounts = [];
        $skippedProviders = [];
        $tried = 0;

        if ($candidates === []) {
            throw new AiProviderException('ai-router', 'Tidak ada kandidat model yang layak untuk multi-transaksi.');
        }

        foreach ($candidates as $index => $candidate) {
            $account = $candidate['account'];
            $provider = $candidate['provider'];
            $modelId = (string) $candidate['model_id'];
            $catalog = $candidate['model'] instanceof AiModelCatalog ? $candidate['model'] : null;

            if (isset($skippedAccounts[$account->id]) || isset($skippedProviders[$provider->value])) {
                continue;
            }
            if ($tried >= $maxAttempts || (microtime(true) - $started) > self::TOTAL_BUDGET_SECONDS) {
                break;
            }
            $retriesLeft = 1;
            while (true) {
                if ($tried >= $maxAttempts || (microtime(true) - $started) > self::TOTAL_BUDGET_SECONDS) {
                    break 2;
                }
                $tried++;
                $t0 = microtime(true);
                try {
                    $result = $attempt($account, $modelId, $provider);
                    $latency = (int) round((microtime(true) - $t0) * 1000);
                    $attempts[] = $this->attemptRecord($provider, $account, $modelId, $latency, 'OK', null, $index, 1);
                    if ($account->id > 0) {
                        $this->health->recordSuccess($account->fresh(), $catalog?->exists ? $catalog->fresh() : null);
                    }

                    return ['result' => $result, 'routing' => [
                        'trace_id' => $traceId,
                        'selected_provider' => $provider->value,
                        'selected_account_id' => $account->id,
                        'selected_account_alias' => $account->alias ?? null,
                        'selected_model' => $modelId,
                        'selection_method' => $candidate['source'] ?? 'unknown',
                        'resolver_method' => $resolved['method'] ?? 'unknown',
                        'attempt_count' => $tried,
                        'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                        'attempts' => $attempts,
                        'failures' => $failures,
                        'skipped' => $resolved['skipped'] ?? [],
                        'final_status' => 'success',
                    ]];
                } catch (\Throwable $e) {
                    $latency = (int) round((microtime(true) - $t0) * 1000);
                    $classified = AiErrorClassifier::classify($e);
                    $attempts[] = $this->attemptRecord($provider, $account, $modelId, $latency, $classified['category'], $classified['safe_message'], $index, 1);
                    $failures[] = ['provider' => $provider->value, 'account_id' => $account->id, 'model' => $modelId, 'category' => $classified['category'], 'action' => $classified['action']];
                    if ($account->id > 0) {
                        $this->health->recordFailure($account->fresh(), $catalog?->exists ? $catalog->fresh() : null, $classified, $e->getMessage());
                    }
                    if ($classified['action'] === AiErrorClassifier::ACTION_PERMANENT) {
                        throw new AiProviderException($provider->value, $classified['safe_message'].' (tidak dicoba ke kandidat lain).');
                    }
                    if ($classified['action'] === AiErrorClassifier::ACTION_NEXT_PROVIDER) {
                        $skippedProviders[$provider->value] = true;
                        break;
                    }
                    if ($classified['action'] === AiErrorClassifier::ACTION_NEXT_ACCOUNT) {
                        $skippedAccounts[$account->id] = true;
                        break;
                    }
                    if ($classified['action'] === AiErrorClassifier::ACTION_RETRY_SAME && $classified['retryable'] && $retriesLeft > 0) {
                        $retriesLeft--;
                        $sleepFn($this->backoffSeconds(1, $classified['retry_after_s']));

                        continue;
                    }
                    break;
                }
            }
        }
        throw new AiProviderException('ai-router', 'Semua kandidat multi-transaksi gagal ('.$tried.' percobaan).');
    }

    /**
     * Eksekusi generateText lintas kandidat (untuk budget/laporan/evidence).
     *
     * @param  callable(AiProviderAccount, string $model, AiProvider $provider): string  $attempt
     * @return array{text:string, routing:array}
     */
    public function executeText(User $user, callable $attempt, ?AiRoutingPreference $routing = null, ?callable $sleepFn = null): array
    {
        $wrap = function (AiProviderAccount $account, string $model, AiProvider $provider) use ($attempt) {
            $text = $attempt($account, $model, $provider);

            // Bungkus sebagai AIParseResult-like minimal agar executor seragam.
            return new AIParseResult(success: true, confidence: 1.0, error: null, transaction: null, usage: ['prompt' => 0, 'completion' => 0, 'total' => 0], provider: $provider->value, model: $model, rawText: $text);
        };
        // Simpan teks mentah di rawText via DTO kustom? AIParseResult tidak punya rawText — gunakan closure di bawah.
        // Untuk menyederhanakan, panggil ulang dengan adaptor langsung dan tangkap string.
        $resolved = $this->resolver->resolve($user, $routing);
        $candidates = $resolved['candidates'];
        $traceId = (string) \Str::uuid();
        $started = microtime(true);
        $maxAttempts = max(1, min(12, (int) ($routing?->max_attempts ?? 8)));
        $attempts = [];
        $failures = [];
        $skippedAccounts = [];
        $skippedProviders = [];
        $tried = 0;
        $sleepFn ??= fn (float $s) => usleep((int) round($s * 1_000_000));

        if ($candidates === []) {
            throw new AiProviderException('ai-router', 'Tidak ada kandidat model yang layak.');
        }

        foreach ($candidates as $index => $candidate) {
            $account = $candidate['account'];
            $provider = $candidate['provider'];
            $modelId = (string) $candidate['model_id'];
            if (isset($skippedAccounts[$account->id]) || isset($skippedProviders[$provider->value])) {
                continue;
            }
            if ($tried >= $maxAttempts || (microtime(true) - $started) > self::TOTAL_BUDGET_SECONDS) {
                break;
            }
            $retriesLeft = 1;
            while (true) {
                if ($tried >= $maxAttempts || (microtime(true) - $started) > self::TOTAL_BUDGET_SECONDS) {
                    break 2;
                }
                $tried++;
                $t0 = microtime(true);
                try {
                    $text = $attempt($account, $modelId, $provider);
                    $latency = (int) round((microtime(true) - $t0) * 1000);
                    $attempts[] = $this->attemptRecord($provider, $account, $modelId, $latency, 'OK', null, $index, 1);
                    if ($account->id > 0) {
                        $this->health->recordSuccess($account->fresh(), null);
                    }

                    return ['text' => $text, 'routing' => [
                        'trace_id' => $traceId,
                        'selected_provider' => $provider->value,
                        'selected_account_id' => $account->id,
                        'selected_model' => $modelId,
                        'attempt_count' => $tried,
                        'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                        'attempts' => $attempts,
                        'failures' => $failures,
                        'final_status' => 'success',
                    ]];
                } catch (\Throwable $e) {
                    $latency = (int) round((microtime(true) - $t0) * 1000);
                    $classified = AiErrorClassifier::classify($e);
                    $attempts[] = $this->attemptRecord($provider, $account, $modelId, $latency, $classified['category'], $classified['safe_message'], $index, 1);
                    $failures[] = ['provider' => $provider->value, 'account_id' => $account->id, 'model' => $modelId, 'category' => $classified['category'], 'action' => $classified['action']];
                    if ($account->id > 0) {
                        $this->health->recordFailure($account->fresh(), null, $classified, $e->getMessage());
                    }
                    if ($classified['action'] === AiErrorClassifier::ACTION_PERMANENT) {
                        throw new AiProviderException($provider->value, $classified['safe_message']);
                    }
                    if ($classified['action'] === AiErrorClassifier::ACTION_NEXT_PROVIDER) {
                        $skippedProviders[$provider->value] = true;
                        break;
                    }
                    if ($classified['action'] === AiErrorClassifier::ACTION_NEXT_ACCOUNT) {
                        $skippedAccounts[$account->id] = true;
                        break;
                    }
                    if ($classified['action'] === AiErrorClassifier::ACTION_RETRY_SAME && $classified['retryable'] && $retriesLeft > 0) {
                        $retriesLeft--;
                        $sleepFn($this->backoffSeconds(1, $classified['retry_after_s']));

                        continue;
                    }
                    break;
                }
            }
        }
        throw new AiProviderException('ai-router', 'Semua kandidat generateText gagal ('.$tried.' percobaan).');
    }

    private function backoffSeconds(int $attemptNo, ?float $retryAfter): float
    {
        if ($retryAfter !== null && $retryAfter > 0) {
            return min($retryAfter, 2.0);
        }
        // Exponential backoff kecil + jitter agar tidak thundering herd, tetap dalam budget chat.
        $base = 0.2 * (2 ** max(0, $attemptNo - 1)); // 0.2s, 0.4s
        $jitter = mt_rand(0, 100) / 1000.0;

        return min(1.0, $base + $jitter);
    }

    private function attemptRecord(AiProvider $provider, AiProviderAccount $account, string $model, int $latencyMs, string $category, ?string $message, int $index, int $attemptNo = 1): array
    {
        return [
            'provider' => $provider->value,
            'account_id' => $account->id,
            'account_alias' => $account->alias ?? null,
            'model' => $model,
            'latency_ms' => $latencyMs,
            'category' => $category,
            'message' => $message,
            'fallback_index' => $index,
            'attempt' => $attemptNo,
        ];
    }
}
