<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\User;
use App\Models\AiUsageLog;
use App\DTO\AIParseResult;
use App\DTO\AiProviderRequest;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiProviderException;
use App\Services\AI\Providers\PythonNLPProvider;
use Illuminate\Support\Facades\Log;

readonly class AIManager
{
    public function __construct(
        private AiPreferenceManager $preferenceManager,
        private AiCredentialManager $credentialManager,
        private AiProviderFactory $providerFactory,
        private TransactionValidationService $validator,
        private PythonNLPProvider $pythonNlp
    ) {}

    public function parseTransaction(User $user, string $text, array $wallets = [], array $categories = [], array $activeMemories = []): AIParseResult
    {
        // 1. CIRCUIT BREAKER 1: PYTHON NLP LOKAL (TANPA BIAYA)
        // Injeksi memori (RAG) ke dalam array categories agar Python bisa mencocokkan kata gaul dari sejarah memori!
        $pythonCategories = $categories;
        foreach ($activeMemories as $memory) {
            if (!empty($memory['category']) && !empty($memory['keyword'])) {
                foreach ($pythonCategories as &$cat) {
                    if ($cat['category_name'] === $memory['category']) {
                        $cat['keyword'] = ($cat['keyword'] ?? '') . ',' . $memory['keyword'];
                    }
                }
            }
        }

        $pythonRequest = new AiProviderRequest($text, '', 'local-nlp', $wallets, $pythonCategories, []);
        
        $pythonResult = null; // Initialise supaya undefined variable tidak berlaku jika Python down
        try {
            $pythonResult = $this->pythonNlp->parseTransaction($pythonRequest);
            // Hanya terima hasil Python jika AI "Sangat Yakin" (Skor > 0.85)
            if ($pythonResult->success && $pythonResult->confidence >= 0.85) {
                return $this->validator->validateAndGuard($pythonResult);
            }
        } catch (\Throwable $e) {
            Log::warning("Python NLP Service Down/Timeout: " . $e->getMessage());
        }

        // 2. CIRCUIT BREAKER 2: FALLBACK KE LLM (GEMINI/OPENAI)
        $preference = $this->preferenceManager->getActivePreference($user);
        
        // Jika user belum setup AI Gemini/OpenAI di web, jangan crash! 
        // Kembalikan saja hasil dari Python (walaupun confidence-nya rendah).
        if (!$preference) {
            if ($pythonResult !== null && $pythonResult->success) {
                Log::info("AIManager: Tiada LLM setup, guna hasil Python (confidence rendah) untuk user #{$user->id}");
                return $this->validator->validateAndGuard($pythonResult);
            }
            throw new AiConfigurationException("Sistem AI gagal memproses transaksi. Python service offline dan LLM (Gemini/OpenAI) belum dikonfigurasi. Sila setup AI di tetapan akaun.");
        }

        $providerEnum = $preference->provider;
        $credential = $this->credentialManager->getCredential($user, $providerEnum);

        if (!$credential || blank($credential->api_key) || !$credential->is_valid) {
            throw new AiConfigurationException("API Key untuk '{$providerEnum->value}' bermasalah.");
        }

        $providerInstance = $this->providerFactory->make($providerEnum);
        $model = $preference->selected_model ?? $providerEnum->defaultModel();

        // LLM Menerima injeksi memori (RAG)
        $llmRequest = new AiProviderRequest(
            text: $text, 
            apiKey: $credential->api_key, 
            model: $model, 
            wallets: $wallets, 
            categories: $categories, 
            activeMemories: $activeMemories 
        );

        try {
            $llmResult = $providerInstance->parseTransaction($llmRequest);

            // Catat Token Usage hanya jika pakai LLM Eksternal
            if ($llmResult->usage['total'] > 0) {
                AiUsageLog::create([
                    'user_id'           => $user->id,
                    'provider'          => $providerEnum->value,
                    'model'             => $model,
                    'prompt_tokens'     => $llmResult->usage['prompt'],
                    'completion_tokens' => $llmResult->usage['completion'],
                    'total_tokens'      => $llmResult->usage['total'],
                ]);
            }

            return $this->validator->validateAndGuard($llmResult);

        } catch (AiRateLimitException | AiTimeoutException | AiProviderException $e) {
            // Re-throw langsung — biar Orchestrator yang handle pesan Telegram-nya
            throw $e;
        } catch (\Throwable $e) {
            Log::error("LLM Provider {$providerEnum->value} Crash: " . $e->getMessage());
            throw new AiProviderException($providerEnum->value, $e->getMessage());
        }
    }
}