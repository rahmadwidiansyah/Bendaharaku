<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AIParseResult;
use App\DTO\AiProviderRequest;
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
        // SPEC §4: LLM is source of truth; LocalRuleEngine MUST NOT override valid LLM result.
        // Priority: 1) LLM valid result 2) Deterministic fallback if LLM unavailable 3) null
        // Keep LRE result for fallback comparison, but do NOT return immediately — try LLM first.
        $ruleEngineResult = $this->ruleEngine->parse($user, $text);
        if ($ruleEngineResult !== null && $ruleEngineResult->success) {
            Log::debug('AIManager: [LRE] Parsed but holding as fallback (will try LLM first)', [
                'user_id' => $user->id,
                'text' => mb_substr($text, 0, 100),
                'intent' => $ruleEngineResult->transaction?->transactionType?->value,
                'amount' => $ruleEngineResult->transaction?->amount,
                'source_wallet' => $ruleEngineResult->transaction?->sourceWallet,
            ]);
        } else {
            Log::debug('AIManager: [LRE] Rule engine returned null or failed, proceeding to LLM', [
                'user_id' => $user->id,
                'text' => mb_substr($text, 0, 100),
                'rule_engine_result' => $ruleEngineResult !== null ? 'exists' : 'null',
            ]);
        }

        // 1. CIRCUIT BREAKER 1: PYTHON NLP LOKAL (TANPA BIAYA) — low confidence only
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
        $preference = $this->preferenceManager->resolveActivePreference($user);

        if (! $preference) {
            if ($pythonResult !== null && $pythonResult->success) {
                Log::info("AIManager: Tiada LLM setup, guna hasil Python (confidence rendah) untuk user #{$user->id}");

                return $pythonResult;
            }
            // SPEC §4: Fallback to LRE only if LLM unavailable and LRE valid, else null (no fabrication)
            if ($ruleEngineResult !== null && $ruleEngineResult->success) {
                Log::info("AIManager: No LLM preference, fallback to LRE as deterministic fallback for user #{$user->id}");

                return $ruleEngineResult;
            }
            throw new AiConfigurationException('Sistem AI gagal memproses transaksi. Python service offline dan LLM (Gemini/OpenAI) belum dikonfigurasi. Sila setup AI di tetapan akaun.');
        }

        $credential = $this->credentialManager->getCredential($user, $preference->provider);
        if (! $credential || blank($credential->api_key) || ! $credential->is_valid) {
            // Before throwing, try deterministic fallback (Python then LRE) per SPEC §4 priority 2
            if ($pythonResult !== null && $pythonResult->success) {
                Log::info("AIManager: Credential invalid, fallback to Python for user #{$user->id}");

                return $pythonResult;
            }
            if ($ruleEngineResult !== null && $ruleEngineResult->success) {
                Log::info("AIManager: Credential invalid, fallback to LRE for user #{$user->id}");

                return $ruleEngineResult;
            }
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
            // SPEC §16: On Gemini timeout, DO NOT fabricate amount — fallback with LLM_UNAVAILABLE warning
            // Try deterministic fallback (Python then LRE) before rethrowing
            if ($pythonResult !== null && $pythonResult->success) {
                Log::warning("AIManager: LLM {$e->getMessage()} fallback to Python for user #{$user->id}");

                return $pythonResult;
            }
            if ($ruleEngineResult !== null && $ruleEngineResult->success) {
                // Validate LRE amount not suspicious (not year/ID) before fallback — AmountExtractor already tightened
                $lreAmount = $ruleEngineResult->transaction?->amount;
                if ($lreAmount !== null && $lreAmount >= 1900 && $lreAmount <= 2100) {
                    Log::warning("AIManager: LRE amount {$lreAmount} looks like year, NOT using as fallback (return null for manual review)");
                    throw $e;
                }
                Log::warning("AIManager: LLM {$e->getMessage()} fallback to LRE for user #{$user->id}");

                return $ruleEngineResult;
            }
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
            // Try fallback before throwing
            if ($pythonResult !== null && $pythonResult->success) {
                return $pythonResult;
            }
            if ($ruleEngineResult !== null && $ruleEngineResult->success) {
                return $ruleEngineResult;
            }
            throw new AiProviderException($preference->provider->value, $e->getMessage());
        }
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
