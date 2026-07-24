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
use App\Services\AI\Prompt\TransactionPromptBuilder;
use App\Services\AI\Providers\PythonNLPProvider;
use Illuminate\Support\Facades\Log;

class AIManager
{
    public function __construct(
        private AiPreferenceManager $preferenceManager,
        private AiCredentialManager $credentialManager,
        private AiProviderFactory $providerFactory,
        private TransactionPromptBuilder $promptBuilder,
        private PythonNLPProvider $pythonNlp,
        private LocalRuleEngine $ruleEngine,
    ) {}

    public function parseTransaction(User $user, string $text, array $wallets = [], array $categories = [], array $activeMemories = [], ?string $prompt = null): AIParseResult
    {
        // 0. LOCAL RULE ENGINE (ZERO-LATENCY REGEX & KEYWORDS)
        $ruleEngineResult = $this->ruleEngine->parse($user, $text);
        if ($ruleEngineResult !== null && $ruleEngineResult->success) {
            return $ruleEngineResult;
        }

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
        $prompt = $prompt ?? $this->promptBuilder->build(
            $text, $wallets, $categories, $activeMemories
        );

        try {
            $llmResult = $adapter->parseTransaction(
                prompt: $prompt,
                apiKey: $credential->api_key,
                model: $model,
                fallbackText: $text,
            );

            if ($llmResult->usage['total'] > 0) {
                AiUsageLog::create([
                    'user_id' => $user->id,
                    'provider' => $preference->provider->value,
                    'model' => $model,
                    'prompt_tokens' => $llmResult->usage['prompt'],
                    'completion_tokens' => $llmResult->usage['completion'],
                    'total_tokens' => $llmResult->usage['total'],
                ]);
            }

            return $llmResult;

        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error("LLM Provider {$preference->provider->value} Crash: ".$e->getMessage());
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
