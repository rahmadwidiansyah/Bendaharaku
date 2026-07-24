<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AIParseResult;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\AI\Providers\PythonNLPProvider;
use Illuminate\Support\Facades\Log;

class AIManager
{
    public function __construct(
        private AiPreferenceManager $preferenceManager,
        private AiCredentialManager $credentialManager,
        private AiProviderFactory $providerFactory,
        private PythonNLPProvider $pythonNlp,
        private LocalRuleEngine $ruleEngine,
    ) {}

    public function parseTransaction(User $user, string $text, array $wallets = [], array $categories = [], array $activeMemories = [], string $prompt = ''): AIParseResult
    {
        // 0. LOCAL RULE ENGINE (ZERO-LATENCY REGEX & KEYWORDS)
        $ruleEngineResult = $this->ruleEngine->parse($user, $text);
        if ($ruleEngineResult !== null && $ruleEngineResult->success) {
            Log::debug('AIManager: [LRE] Circuit breaker returning rule engine result', [
                'user_id' => $user->id,
                'text' => $text,
                'success' => $ruleEngineResult->success,
                'intent' => $ruleEngineResult->transaction?->transactionType?->value,
                'source_wallet' => $ruleEngineResult->transaction?->sourceWallet,
                'is_cleared' => $ruleEngineResult->transaction?->isCleared,
                'category' => $ruleEngineResult->transaction?->category,
            ]);
            return $ruleEngineResult;
        }

        Log::debug('AIManager: [LRE] Rule engine returned null or failed, proceeding to fallbacks', [
            'user_id' => $user->id,
            'text' => $text,
            'rule_engine_result' => $ruleEngineResult !== null ? 'exists' : 'null',
        ]);

        // 1. CIRCUIT BREAKER 1: PYTHON NLP LOKAL (TANPA BIAYA)
        $pythonCategories = $categories;
        foreach ($activeMemories as $memory) {
            if (! empty($memory['category']) && ! empty($memory['keyword'])) {
                foreach ($pythonCategories as &$cat) {
                    if ($cat['category_name'] === $memory['category']) {
                        $cat['keyword'] = ($cat['keyword'] ?? '').','.$memory['keyword'];
                    }
                }
            }
        }

        $pythonResult = $this->runPythonNlp($text, $wallets, $pythonCategories);

        // 2. CIRCUIT BREAKER 2: FALLBACK KE LLM (GEMINI/OPENAI)
        $preference = $this->preferenceManager->getActivePreference($user);

        if (! $preference) {
            if ($pythonResult !== null && $pythonResult->success) {
                Log::info("AIManager: Tiada LLM setup, guna hasil Python (confidence rendah) untuk user #{$user->id}");
                return $pythonResult;
            }
            throw new AiConfigurationException('Sistem AI gagal memproses transaksi. Python service offline dan LLM (Gemini/OpenAI) belum dikonfigurasi. Sila setup AI di tetapan akaun.');
        }

        $credential = $this->credentialManager->getCredential($user, $preference->provider);
        if (! $credential || blank($credential->api_key) || ! $credential->is_valid) {
            throw new AiConfigurationException("API Key untuk '{$preference->provider->value}' bermasalah.");
        }

        $adapter = $this->providerFactory->make($preference->provider);
        $model = $preference->selected_model ?? $preference->provider->defaultModel();

        try {
            $llmResult = $adapter->parseTransaction(
                prompt: $prompt,
                apiKey: $credential->api_key,
                model: $model,
                fallbackText: $text,
            );

            $usage = $llmResult->usage;
            $totalTokens = (int) ($usage['total'] ?? 0);

            if ($totalTokens > 0) {
                AiUsageLog::create([
                    'user_id' => $user->id,
                    'provider' => $preference->provider->value,
                    'model' => $model,
                    'prompt_tokens' => $usage['prompt'] ?? 0,
                    'completion_tokens' => $usage['completion'] ?? 0,
                    'total_tokens' => $totalTokens,
                ]);
            }

            return $llmResult;

        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('LLM Provider Crash', [
                'provider' => $preference->provider->value,
                'model' => $model,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new AiProviderException($preference->provider->value, $e->getMessage());
        }
    }

    private function runPythonNlp(string $text, array $wallets, array $categories): ?AIParseResult
    {
        try {
            $pythonRequest = new \App\DTO\AiProviderRequest($text, '', 'local-nlp', $wallets, $categories, []);
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
