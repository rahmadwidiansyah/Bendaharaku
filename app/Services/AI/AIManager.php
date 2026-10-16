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
        private AiPreferenceManager $preferenceManager,
        private AiCredentialManager $credentialManager,
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
        // Baru: routing pool lintas akun/provider; fallback lokal (Python→LRE) hanya saat pool gagal (lazy).
        $routing = AiRoutingPreference::where('user_id', $user->id)->first();

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
                return new AIParseResult(
                    success: $llmResult->success,
                    confidence: $llmResult->confidence,
                    error: $llmResult->error,
                    transaction: $llmResult->transaction,
                    usage: $llmResult->usage,
                    provider: $llmResult->provider,
                    model: $llmResult->model,
                    routing: $meta,
                );
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
            // Tidak ada kandidat layak: coba legacy single-provider sekali (BC untuk instalasi lama),
            // lalu fallback lokal. Ini menjaga perilaku lama saat tabel pool kosong.
            $legacy = $this->tryLegacySingle($user, $text, $prompt);
            if ($legacy) {
                return $legacy;
            }
            $poolError = new AiConfigurationException('Tidak ada kandidat model yang layak (semua akun cooldown/invalid atau terfilter kebijakan).');
            Log::warning('AIManager: no pool candidates, trying local fallback', ['user_id' => $user->id, 'skipped' => $resolved['skipped'] ?? []]);
        }

        // ── FALLBACK LOKAL (lazy: hanya saat pool gagal) ──
        $pythonResult = $this->runPythonNlp($text, $wallets, $this->injectMemories($categories, $activeMemories));
        if ($pythonResult !== null && $pythonResult->success) {
            Log::info("AIManager: pool failed, fallback to Python for user #{$user->id}");

            return $this->withLocalRouting($pythonResult, 'local_python_fallback', $poolError ?? null);
        }

        $ruleEngineResult = $this->ruleEngine->parse($user, $text);
        if ($ruleEngineResult !== null && $ruleEngineResult->success) {
            $lreAmount = $ruleEngineResult->transaction?->amount;
            if ($lreAmount !== null && $lreAmount >= 1900 && $lreAmount <= 2100) {
                Log::warning("AIManager: LRE amount {$lreAmount} looks like year, NOT using as fallback");
                throw $this->wrapPoolError($poolError ?? new AiProviderException('ai-router', 'LLM gagal dan LRE ditolak (year guard).'));
            }
            Log::warning("AIManager: pool failed, fallback to LRE for user #{$user->id}");

            return $this->withLocalRouting($ruleEngineResult, 'local_rule_fallback', $poolError ?? null);
        }

        throw $this->wrapPoolError($poolError ?? new AiProviderException('ai-router', 'AI gagal memproses.'));
    }

    /** Jalur legacy satu-provider (BC). Return null jika tidak ada preferensi/kredensial. */
    private function tryLegacySingle(User $user, string $text, string $prompt): ?AIParseResult
    {
        try {
            $preference = $this->preferenceManager->resolveActivePreference($user);
            if (! $preference) {
                return null;
            }
            $credential = $this->credentialManager->getCredential($user, $preference->provider);
            if (! $credential || blank($credential->api_key) || ! $credential->is_valid) {
                return null;
            }
            $adapter = $this->providerFactory->make($preference->provider);
            $model = $preference->selected_model ?? $preference->provider->defaultModel();
            $result = $adapter->parseTransaction(prompt: $prompt, apiKey: $credential->api_key, model: $model, fallbackText: $text);
            $total = (int) ($result->usage['total'] ?? 0);
            if ($total > 0) {
                AiUsageLog::create([
                    'user_id' => $user->id,
                    'provider' => $preference->provider->value,
                    'model' => $model,
                    'prompt_tokens' => $result->usage['prompt'] ?? 0,
                    'completion_tokens' => $result->usage['completion'] ?? 0,
                    'total_tokens' => $total,
                ]);
            }

            return new AIParseResult(
                success: $result->success,
                confidence: $result->confidence,
                error: $result->error,
                transaction: $result->transaction,
                usage: $result->usage,
                provider: $result->provider,
                model: $result->model,
                routing: ['selection_method' => 'legacy_single', 'selected_provider' => $preference->provider->value, 'selected_model' => $model, 'attempt_count' => 1, 'final_status' => 'success'],
            );
        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            Log::warning('AIManager: legacy single failed: '.$e->getMessage(), ['user_id' => $user->id]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('AIManager: legacy single crash: '.$e->getMessage(), ['user_id' => $user->id]);

            return null;
        }
    }

    private function withLocalRouting(AIParseResult $result, string $method, ?\Throwable $poolError): AIParseResult
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
                'selection_method' => $method,
                'attempt_count' => 1,
                'final_status' => 'local_fallback',
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

    /** @param list<array> $categories */
    private function injectMemories(array $categories, array $activeMemories): array
    {
        $pythonCategories = $categories;
        foreach ($activeMemories as $memory) {
            if (! empty($memory['category']) && ! empty($memory['keyword'])) {
                foreach ($pythonCategories as &$cat) {
                    if (($cat['category_name'] ?? null) === $memory['category']) {
                        $cat['keyword'] = ($cat['keyword'] ?? '').','.$memory['keyword'];
                    }
                }
                unset($cat);
            }
        }

        return $pythonCategories;
    }

    private function runPythonNlp(string $text, array $wallets, array $categories): ?AIParseResult
    {
        try {
            $pythonRequest = new AiProviderRequest($text, '', 'local-nlp', $wallets, $categories, []);
            $pythonResult = $this->pythonNlp->parseTransaction($pythonRequest);
            if ($pythonResult->success && $pythonResult->confidence >= 0.85) {
                return $pythonResult;
            }
        } catch (\Throwable $e) {
            Log::warning('Python NLP Service Down/Timeout: '.$e->getMessage());
        }

        return null;
    }
}
