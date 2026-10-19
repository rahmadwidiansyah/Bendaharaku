<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AIParseResult;
use App\DTO\AiProviderRequest;
use App\Enums\AiProvider;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Models\AiRoutingPreference;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\AI\Providers\PythonNLPProvider;
use App\Services\AI\Routing\AiRoutingExecutor;
use App\Services\AI\Routing\ModelResolver;
use Illuminate\Support\Facades\Log;

class AIManager
{
    public function __construct(
        private AiProviderFactory $providerFactory,
        private PythonNLPProvider $pythonNlp,
        private LocalRuleEngine $ruleEngine,
        private ?ModelResolver $resolver = null,
        private ?AiRoutingExecutor $executor = null,
    ) {
        // Lazy resolve agar unit test lama tanpa binding pool tetap jalan.
        $this->resolver ??= app(ModelResolver::class);
        $this->executor ??= app(AiRoutingExecutor::class);
    }

    public function parseTransaction(User $user, string $text, array $wallets = [], array $categories = [], array $activeMemories = [], string $prompt = ''): AIParseResult
    {
        // SPEC §4: LLM is source of truth; LocalRuleEngine MUST NOT override valid LLM result.
        // Single: Python NLP dicoba lebih dulu (gratis & cepat); pool LLM saat Python tidak yakin/gagal.
        // Pool gagal/habis: fallback berjenjang Python (ambang lebih rendah) → LRE.
        $routing = AiRoutingPreference::where('user_id', $user->id)->first();

        $pythonFirst = (bool) config('bendaharaku.ai.parser.python_first', true);
        $pythonPayload = $this->injectMemories($wallets, $categories, $activeMemories);
        $pythonResult = null;

        if ($pythonFirst) {
            $pythonResult = $this->fetchPythonNlp($text, $pythonPayload['wallets'], $pythonPayload['categories']);
            $minPrimary = (float) config('bendaharaku.ai.parser.min_confidence', 0.85);

            if ($pythonResult !== null && $pythonResult->success && $pythonResult->confidence >= $minPrimary) {
                Log::info('AIManager: python-first success', [
                    'user_id' => $user->id,
                    'confidence' => $pythonResult->confidence,
                ]);

                return $this->withLocalRouting($pythonResult, 'local_python_primary', null, 'local_success');
            }
        }

        try {
            $resolved = $this->resolver->resolve($user, $routing);
        } catch (\Throwable $e) {
            Log::warning('AIManager: resolver failed, fallback to legacy single-provider', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $resolved = ['candidates' => [], 'skipped' => [], 'method' => 'resolver_error'];
        }

        if (($resolved['candidates'] ?? []) !== []) {
            try {
                $out = $this->executor->executeParse($user, function ($account, string $model, AiProvider $provider) use ($prompt, $text) {
                    // Per-account base_url override untuk openai-compatible.
                    $apiKey = (string) ($account->id > 0 ? $account->api_key : config('bendaharaku.ai.openai_compatible.api_key'));
                    if (blank($apiKey)) {
                        throw new AiConfigurationException("API Key untuk '{$provider->value}' kosong.");
                    }
                    if ($provider === AiProvider::OpenAiCompatible && ! empty($account->base_url)) {
                        config()->set('bendaharaku.ai.openai_compatible.base_url', $account->base_url);
                    }
                    $adapter = $this->providerFactory->make($provider);

                    return $adapter->parseTransaction(
                        prompt: $prompt,
                        apiKey: $apiKey,
                        model: $model,
                        fallbackText: $text,
                    );
                }, $routing);

                /** @var AIParseResult $llmResult */
                $llmResult = $out['result'];
                $meta = $out['routing'];

                $usage = $llmResult->usage;
                $totalTokens = (int) ($usage['total'] ?? 0);
                if ($totalTokens > 0) {
                    AiUsageLog::create([
                        'user_id' => $user->id,
                        'provider' => $meta['selected_provider'] ?? $llmResult->provider,
                        'model' => $meta['selected_model'] ?? $llmResult->model,
                        'account_id' => ($meta['selected_account_id'] ?? 0) > 0 ? $meta['selected_account_id'] : null,
                        'trace_id' => $meta['trace_id'] ?? null,
                        'attempt_count' => $meta['attempt_count'] ?? 1,
                        'selection_method' => $meta['selection_method'] ?? null,
                        'prompt_tokens' => $usage['prompt'] ?? 0,
                        'completion_tokens' => $usage['completion'] ?? 0,
                        'total_tokens' => $totalTokens,
                    ]);
                }

                Log::info('AIManager: pool success', [
                    'user_id' => $user->id,
                    'trace_id' => $meta['trace_id'] ?? null,
                    'selected' => ($meta['selected_provider'] ?? '?').'/'.($meta['selected_model'] ?? '?').'#'.($meta['selected_account_id'] ?? 0),
                    'attempts' => $meta['attempt_count'] ?? 1,
                    'method' => $meta['selection_method'] ?? null,
                ]);

                // Lampirkan routing agar orchestrator/log bisa catat fallback_* tanpa query ulang.
                $result = new AIParseResult(
                    success: $llmResult->success,
                    confidence: $llmResult->confidence,
                    error: $llmResult->error,
                    transaction: $llmResult->transaction,
                    usage: $llmResult->usage,
                    provider: $llmResult->provider,
                    model: $llmResult->model,
                    routing: $meta,
                );

                if (! empty($meta['fallback_from'])) {
                    AiFallbackActivityLogger::log(
                        $user,
                        (string) $meta['fallback_from'],
                        (string) ($meta['fallback_to'] ?? $llmResult->provider.'/'.$llmResult->model),
                        $meta,
                    );
                }

                return $result;
            } catch (AiProviderException $e) {
                // Pool habis atau permanen: lanjut ke fallback lokal di bawah.
                // Jangan log API key; $e sudah safe (classifier safe_message).
                Log::warning('AIManager: pool exhausted, trying local fallback', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $poolError = $e;
            } catch (\Throwable $e) {
                Log::error('AIManager: pool crash', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $poolError = $e;
            }
        } else {
            // Tidak ada kandidat layak: langsung fallback lokal.
            // Jalur legacy single-provider sudah di-retire (Opsi A).
            $poolError = new AiConfigurationException('Tidak ada kandidat model yang layak (semua akun cooldown/invalid atau terfilter kebijakan).');
            Log::warning('AIManager: no pool candidates, trying local fallback', ['user_id' => $user->id, 'skipped' => $resolved['skipped'] ?? []]);
        }

        // ── FALLBACK LOKAL (lazy: hanya saat pool gagal) ──
        // Kandidat pool ada tapi semuanya gagal = fallback nyata; tanpa kandidat = jalur normal.
        $hadPoolCandidates = ($resolved['candidates'] ?? []) !== [];

        // Python-first sudah memanggil service di awal — pakai hasil yang di-hold (1 HTTP call per pesan).
        if (! $pythonFirst) {
            $pythonResult = $this->fetchPythonNlp($text, $pythonPayload['wallets'], $pythonPayload['categories']);
        }

        $minFallback = (float) config('bendaharaku.ai.parser.min_confidence_fallback', 0.6);
        if ($pythonResult !== null && $pythonResult->success && $pythonResult->confidence >= $minFallback) {
            Log::info("AIManager: pool failed, fallback to Python for user #{$user->id}");

            $result = $this->withLocalRouting($pythonResult, 'local_python_fallback', $poolError ?? null);
            $this->logLocalFallback($user, $result, $hadPoolCandidates);

            return $result;
        }

        $ruleEngineResult = $this->ruleEngine->parse($user, $text);
        if ($ruleEngineResult !== null && $ruleEngineResult->success) {
            $lreAmount = $ruleEngineResult->transaction?->amount;
            if ($lreAmount !== null && $lreAmount >= 1900 && $lreAmount <= 2100) {
                Log::warning("AIManager: LRE amount {$lreAmount} looks like year, NOT using as fallback");
                throw $this->wrapPoolError($poolError ?? new AiProviderException('ai-router', 'LLM gagal dan LRE ditolak (year guard).'));
            }
            Log::warning("AIManager: pool failed, fallback to LRE for user #{$user->id}");

            $result = $this->withLocalRouting($ruleEngineResult, 'local_rule_fallback', $poolError ?? null);
            $this->logLocalFallback($user, $result, $hadPoolCandidates);

            return $result;
        }

        throw $this->wrapPoolError($poolError ?? new AiProviderException('ai-router', 'AI gagal memproses.'));
    }

    private function logLocalFallback(User $user, AIParseResult $result, bool $hadPoolCandidates): void
    {
        if (! $hadPoolCandidates) {
            return;
        }

        AiFallbackActivityLogger::log(
            $user,
            'llm-pool',
            trim(($result->provider ?? 'local').'/'.($result->model ?? 'unknown'), '/'),
            $result->routing,
        );
    }

    private function withLocalRouting(AIParseResult $result, string $method, ?\Throwable $poolError = null, string $finalStatus = 'local_fallback'): AIParseResult
    {
        return new AIParseResult(
            success: $result->success,
            confidence: $result->confidence,
            error: $result->error,
            transaction: $result->transaction,
            usage: $result->usage,
            provider: $result->provider, // python-nlp / local
            model: $result->model,
            routing: [
                'selected_provider' => $result->provider,
                'selected_model' => $result->model,
                'selection_method' => $method,
                'attempt_count' => 1,
                'final_status' => $finalStatus,
                'fallback_reason' => $poolError ? mb_substr($poolError->getMessage(), 0, 200) : null,
            ],
        );
    }

    private function wrapPoolError(\Throwable $e): \Throwable
    {
        // Jika sudah klasifikasi aman (AiProviderException dari executor), teruskan.
        if ($e instanceof AiProviderException || $e instanceof AiRateLimitException || $e instanceof AiTimeoutException || $e instanceof AiConfigurationException) {
            return $e;
        }

        return new AiProviderException('ai-router', $e->getMessage());
    }

    /**
     * Suntik keyword memory dari LLM ke payload Python (kategori + wallet).
     *
     * @param  list<array>  $wallets
     * @param  list<array>  $categories
     * @param  list<array>  $activeMemories
     * @return array{wallets: list<array>, categories: list<array>}
     */
    private function injectMemories(array $wallets, array $categories, array $activeMemories): array
    {
        $outWallets = $wallets;
        $outCategories = $categories;

        foreach ($activeMemories as $memory) {
            $keyword = trim((string) ($memory['keyword'] ?? ''));
            if ($keyword === '') {
                continue;
            }

            if (! empty($memory['category'])) {
                $this->mergeMemoryKeyword($outCategories, 'category_name', (string) $memory['category'], $keyword);
            }

            if (! empty($memory['wallet'])) {
                $this->mergeMemoryKeyword($outWallets, 'name', (string) $memory['wallet'], $keyword);
            }
        }

        return ['wallets' => $outWallets, 'categories' => $outCategories];
    }

    /**
     * Isi keyword bila kosong/sentinel '-', selain itu append dengan dedupe.
     *
     * @param  list<array>  $items
     */
    private function mergeMemoryKeyword(array &$items, string $nameKey, string $targetName, string $keyword): void
    {
        foreach ($items as &$item) {
            if (($item[$nameKey] ?? null) !== $targetName) {
                continue;
            }

            $current = trim((string) ($item['keyword'] ?? ''));
            if ($current === '' || $current === '-') {
                $item['keyword'] = $keyword;
            } else {
                $existing = array_map('trim', preg_split('/[,|;]+/', mb_strtolower($current)) ?: []);
                if (! in_array(mb_strtolower($keyword), $existing, true)) {
                    $item['keyword'] = $current.','.$keyword;
                }
            }

            unset($item);

            return;
        }
        unset($item);
    }

    /**
     * Panggil Python NLP tanpa gate confidence — gate ditentukan pemanggil
     * (primary 0.85 saat Python-first, fallback 0.6 saat pool habis).
     */
    private function fetchPythonNlp(string $text, array $wallets, array $categories): ?AIParseResult
    {
        try {
            $pythonRequest = new AiProviderRequest($text, '', 'local-nlp', $wallets, $categories, []);

            return $this->pythonNlp->parseTransaction($pythonRequest);
        } catch (\Throwable $e) {
            Log::warning('Python NLP Service Down/Timeout: '.$e->getMessage());

            return null;
        }
    }
}
