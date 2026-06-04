<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\User;
use App\Models\AiUsageLog;
use App\Models\AiParseLog;
use App\DTO\AIParseResult;
use App\DTO\AiProviderRequest;
use App\Exceptions\AiConfigurationException;
use App\Services\AI\Providers\PythonNLPProvider; // Pastikan use class Python Anda

class AIManager
{
    public function __construct(
        private readonly AiPreferenceManager $preferenceManager,
        private readonly AiCredentialManager $credentialManager,
        private readonly AiProviderFactory $providerFactory,
        private readonly TransactionValidationService $validator,
        private readonly PythonNLPProvider $pythonNlp // Inject Python Provider
    ) {}

    public function parseTransaction(User $user, string $text, array $wallets = [], array $categories = []): AIParseResult
    {
        // ==========================================
        // FASE 1: ACTIVE LEARNING (PYTHON NLP)
        // ==========================================
        // Model dan API Key dikosongkan karena Python membaca dari .env
        $pythonRequest = new AiProviderRequest(
            text: $text, apiKey: '', model: '', wallets: $wallets, categories: $categories
        );

        $pythonResult = $this->pythonNlp->parseTransaction($pythonRequest);

        // Jika Python berhasil dan sangat yakin, potong kompas langsung return! (Hemat Token)
        if ($pythonResult->success && $pythonResult->confidence >= 0.8) {
            $this->logParse($user, 'python-nlp', 'regex-fuzzy', $text, $pythonResult);
            return $this->validator->validateAndGuard($pythonResult);
        }

        // Jika Python berhasil jalan tapi ragu (Confidence < 0.8), kita catat dulu 
        // sebagai 'Draft Dataset' biar Anda tahu kelemahan regex-nya di mana.
        if ($pythonResult->success) {
            $this->logParse($user, 'python-nlp', 'regex-fuzzy', $text, $pythonResult);
        }

        // ==========================================
        // FASE 2: FALLBACK KE LLM (GEMINI / OPENAI)
        // ==========================================
        $preference = $this->preferenceManager->getActivePreference($user);
        
        if (!$preference) {
            throw new AiConfigurationException("Python gagal/ragu, dan tidak ada AI Provider aktif untuk Fallback.");
        }

        $providerEnum = $preference->provider;
        $credential = $this->credentialManager->getCredential($user, $providerEnum);

        if (!$credential || blank($credential->api_key) || !$credential->is_valid) {
            throw new AiConfigurationException("Python gagal memproses, dan API Key untuk '{$providerEnum->value}' bermasalah.");
        }

        $providerInstance = $this->providerFactory->make($providerEnum);
        $model = $preference->selected_model ?? $providerEnum->defaultModel();

        $llmRequest = new AiProviderRequest(
            text: $text, apiKey: $credential->api_key, model: $model, wallets: $wallets, categories: $categories
        );

        // Eksekusi LLM
        $llmResult = $providerInstance->parseTransaction($llmRequest);

        // Simpan Hasil LLM (Ini yang akan jadi Kunci Jawaban / Dataset buat training Python nanti)
        $this->logParse($user, $providerEnum->value, $model, $text, $llmResult);

        // Catat Usage Token LLM
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
    }

    /**
     * Helper memisahkan logika insert ke DB agar kode utama tetap bersih
     */
    private function logParse(User $user, string $provider, string $model, string $text, AIParseResult $result): void
    {
        AiParseLog::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'model' => $model,
            'input_text' => $text,
            'raw_response' => $result->transaction ? json_encode($result->transaction) : null,
            'confidence' => $result->confidence,
            'is_success' => $result->success,
            'error_message' => $result->error,
        ]);
    }
}