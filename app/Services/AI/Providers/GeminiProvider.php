<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Prompt\TransactionPromptBuilder;
use App\DTO\AIParseResult;
use App\DTO\AiProviderRequest;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Throwable;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements AIProviderInterface
{
    public function __construct(
        private readonly TransactionPromptBuilder $promptBuilder
    ) {}

    public function parseTransaction(AiProviderRequest $request): AIParseResult
    {
        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$request->model}:generateContent";
            // WARN-03 fix: teruskan activeMemories agar RAG berfungsi
            $prompt = $this->promptBuilder->build(
                $request->text,
                $request->wallets,
                $request->categories,
                $request->activeMemories
            );

            $response = Http::timeout(15)
                ->retry(2, 1000) // Retry 2x jika timeout/5xx
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url . '?key=' . $request->apiKey, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    // responseMimeType TIDAK didukung di v1 endpoint
                    // JSON tetap diparsing manual via regex cleanup di bawah
                ]);

            if (!$response->successful()) {
                $statusCode = $response->status();

                if ($statusCode === 429) {
                    throw new AiRateLimitException('Gemini');
                }
                if (in_array($statusCode, [408, 503, 504])) {
                    throw new AiTimeoutException('Gemini');
                }
                if ($statusCode === 401 || $statusCode === 403) {
                    throw new AiProviderException('Gemini', "API Key tidak valid atau tidak punya akses (HTTP {$statusCode}).");
                }

                throw new AiProviderException('Gemini', "HTTP {$statusCode}: " . substr($response->body(), 0, 200));
            }

            $jsonString = $response->json('candidates.0.content.parts.0.text');
            // Bersihkan format markdown jika AI membalas dengan ```json ... ```
            $jsonString = preg_replace('/```json\s*|\s*```/', '', (string) $jsonString);
            $aiRaw = json_decode($jsonString, true);

            if (!$aiRaw || !isset($aiRaw['amount'])) {
                throw new AiProviderException('Gemini', 'Response tidak mengandung format transaksi yang valid.');
            }

            // Ekstrak Confidence
            $confidence = isset($aiRaw['confidence']) ? (float) $aiRaw['confidence'] : 0.85;

            // Ekstrak Token Usage Gemini
            $usage = [
                'prompt' => $response->json('usageMetadata.promptTokenCount') ?? 0,
                'completion' => $response->json('usageMetadata.candidatesTokenCount') ?? 0,
                'total' => $response->json('usageMetadata.totalTokenCount') ?? 0,
            ];

            $intentString = $aiRaw['transactionType'] ?? null;
            $transactionIntent = $intentString ? TransactionIntent::tryFrom(strtolower(trim($intentString))) : null;

            $parsedTransaction = new ParsedTransaction(
                amount: (float) $aiRaw['amount'],
                transactionType: $transactionIntent,
                category: $aiRaw['category'] ?? null,
                sourceWallet: $aiRaw['sourceWallet'] ?? null,
                destinationWallet: $aiRaw['destinationWallet'] ?? null,
                subject: $aiRaw['subject'] ?? null,
                notes: $aiRaw['notes'] ?? $request->text,
                isCleared: (bool) ($aiRaw['isCleared'] ?? true)
            );

            return new AIParseResult(
                success:     true,
                confidence:  $confidence,
                error:       null,
                transaction: $parsedTransaction,
                usage:       $usage,
                provider:    'gemini',
                model:       $request->model,
            );

        } catch (AiRateLimitException | AiTimeoutException | AiProviderException $e) {
            // Exception spesifik — biarkan naik ke AIManager
            throw $e;
        } catch (ConnectionException $e) {
            Log::warning('Gemini Connection Timeout', ['message' => $e->getMessage()]);
            throw new AiTimeoutException('Gemini');
        } catch (Throwable $e) {
            Log::error('Gemini Provider Error', ['message' => $e->getMessage(), 'text' => $request->text]);
            throw new AiProviderException('Gemini', $e->getMessage());
        }
    }
}