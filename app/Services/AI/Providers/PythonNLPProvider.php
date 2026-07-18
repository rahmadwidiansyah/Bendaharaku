<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\DTO\AIParseResult;
use App\DTO\AIParseResultMulti;
use App\DTO\AiProviderRequest;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Provider untuk Python NLP local service (FastAPI + thefuzz).
 * Dipanggil sebagai Circuit Breaker 1 sebelum LLM eksternal (Gemini/OpenAI/DeepSeek).
 * Source: https://github.com/rahmadwidiansyah/script_pencatat_keuangan
 *
 * Endpoint: POST /analyze
 * Auth    : Header X-API-KEY
 */
class PythonNLPProvider implements AIProviderInterface
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.python_ai.url', ''), '/');
        $this->apiKey  = (string) config('services.python_ai.key', '');
    }

    public function parseTransaction(AiProviderRequest $request): AIParseResult
    {
        if (blank($this->baseUrl)) {
            Log::warning('PythonNLPProvider: PYTHON_AI_URL tidak dikonfigurasi, skip.');
            return AIParseResult::failure('Python NLP tidak dikonfigurasi.', 'python-nlp', 'local');
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'X-API-KEY'    => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/analyze', [
                    'text'       => $request->text,
                    'wallets'    => $request->wallets,
                    'categories' => $request->categories,
                ]);

            if (!$response->successful()) {
                Log::warning('PythonNLPProvider: HTTP Error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return AIParseResult::failure(
                    "Python NLP Error ({$response->status()}): {$response->body()}",
                    'python-nlp',
                    'local'
                );
            }

            $data = $response->json();

            // Validasi field wajib ada
            if (empty($data['success']) || !isset($data['amount']) || $data['amount'] === null) {
                return AIParseResult::failure(
                    'Python NLP: Response tidak valid atau amount kosong.',
                    'python-nlp',
                    'local'
                );
            }

            // Normalize transaction_type string -> TransactionIntent enum
            $intentString      = strtolower(trim($data['transaction_type'] ?? 'expense'));
            $transactionIntent = TransactionIntent::tryFrom($intentString) ?? TransactionIntent::Expense;

            $parsedTransaction = new ParsedTransaction(
                amount:             (float) $data['amount'],
                transactionType:    $transactionIntent,
                category:           $data['category'] ?? null,
                sourceWallet:       $data['source_wallet'] ?? null,
                destinationWallet:  $data['destination_wallet'] ?? null,
                subject:            $data['subject'] ?? null,
                notes:              $data['notes'] ?? $request->text,
                isCleared:          (bool) ($data['is_cleared'] ?? false),
            );

            $confidence = min(1.0, max(0.0, (float) ($data['confidence'] ?? 0.0)));

            return new AIParseResult(
                success:     true,
                confidence:  $confidence,
                error:       null,
                transaction: $parsedTransaction,
                usage:       ['prompt' => 0, 'completion' => 0, 'total' => 0],
                provider:    'python-nlp',
                model:       'local',
            );

        } catch (ConnectionException $e) {
            Log::warning('PythonNLPProvider: Connection timeout/refused', [
                'url'     => $this->baseUrl,
                'message' => $e->getMessage(),
            ]);
            return AIParseResult::failure('Python NLP timeout/tidak terjangkau.', 'python-nlp', 'local');
        } catch (Throwable $e) {
            Log::error('PythonNLPProvider: Unexpected error', [
                'message' => $e->getMessage(),
                'text'    => $request->text,
            ]);
            return AIParseResult::failure('Python NLP error: ' . $e->getMessage(), 'python-nlp', 'local');
        }
    }

    /**
     * Python NLP service hanya mendukung single transaction (/analyze).
     * Multi-transaction tidak didukung — kembalikan failure agar pipeline
     * melanjutkan ke LLM berikutnya (Gemini/OpenAI/DeepSeek).
     */
    public function parseMultiTransaction(AiProviderRequest $request): AIParseResultMulti
    {
        Log::info('PythonNLPProvider: parseMultiTransaction tidak didukung, skip ke LLM berikutnya.');

        return AIParseResultMulti::failure(
            'Python NLP tidak mendukung multi-transaction parsing.',
            'python-nlp',
            'local'
        );
    }
}
