<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\User;
use App\Models\AiUsageLog;
use App\DTO\AIParseResult;
use App\DTO\AiProviderRequest;
use App\Exceptions\AiConfigurationException;
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
        // Kita tidak mengirimkan memori ke Python karena model Spacy/Regex tidak mendukung RAG
        $pythonRequest = new AiProviderRequest($text, '', 'local-nlp', $wallets, $categories, []);
        
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
        if (!$preference) {
            throw new AiConfigurationException("Python gagal/ragu, dan tidak ada AI Provider aktif untuk Fallback.");
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
                    'user_id' => $user->id,
                    'provider' => $providerEnum->value,
                    'model' => $model,
                    'prompt_tokens' => $llmResult->usage['prompt'],
                    'completion_tokens' => $llmResult->usage['completion'],
                    'total_tokens' => $llmResult->usage['total'],
                ]);
            }

            return $this->validator->validateAndGuard($llmResult);

        } catch (\Throwable $e) {
            Log::error("LLM Provider {$providerEnum->value} Crash: " . $e->getMessage());
            return AIParseResult::failure("Semua jalur AI (Lokal & Cloud) gagal memproses transaksi.");
        }
    }
}