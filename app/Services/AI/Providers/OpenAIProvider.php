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

class OpenAIProvider implements AIProviderInterface
{
    public function __construct(
        private readonly TransactionPromptBuilder $promptBuilder
    ) {}

    public function parseTransaction(AiProviderRequest $request): AIParseResult
    {
        try {
            $url = "https://api.openai.com/v1/chat/completions";
            $prompt = $this->promptBuilder->build(
                $request->text, 
                $request->wallets, 
                $request->categories,
                $request->activeMemories
            );

            $response = Http::timeout(15)->withToken($request->apiKey)
                ->post($url, [
                    'model' => $request->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'response_format' => ['type' => 'json_object']
                ]);

            if (!$response->successful()) {
                $statusCode = $response->status();

                if ($statusCode === 429) {
                    throw new AiRateLimitException('OpenAI');
                }
                if (in_array($statusCode, [408, 503, 504])) {
                    throw new AiTimeoutException('OpenAI');
                }
                if ($statusCode === 401 || $statusCode === 403) {
                    throw new AiProviderException('OpenAI', "API Key tidak valid atau tidak punya akses (HTTP {$statusCode}).");
                }

                throw new AiProviderException('OpenAI', "HTTP {$statusCode}: " . substr($response->body(), 0, 200));
            }

            $jsonString = $response->json('choices.0.message.content');
            // Bersihkan format markdown jika AI membalas dengan ```json ... ```
            $jsonString = preg_replace('/```json\s*|\s*```/', '', (string) $jsonString);
            $aiRaw = json_decode($jsonString, true);

            if (!$aiRaw || !isset($aiRaw['amount'])) {
                throw new AiProviderException('OpenAI', 'Response tidak mengandung format transaksi yang valid.');
            }

            // Ekstrak Confidence
            $confidence = isset($aiRaw['confidence']) ? (float) $aiRaw['confidence'] : 0.85;

            // Ekstrak Token Usage OpenAI
            $usage = [
                'prompt' => $response->json('usage.prompt_tokens') ?? 0,
                'completion' => $response->json('usage.completion_tokens') ?? 0,
                'total' => $response->json('usage.total_tokens') ?? 0,
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
                provider:    'openai',
                model:       $request->model,
            );

        } catch (AiRateLimitException | AiTimeoutException | AiProviderException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::warning('OpenAI Connection Timeout', ['message' => $e->getMessage()]);
            throw new AiTimeoutException('OpenAI');
        } catch (Throwable $e) {
            Log::error('OpenAI Provider Error', ['message' => $e->getMessage(), 'text' => $request->text]);
            throw new AiProviderException('OpenAI', $e->getMessage());
        }
    }
}