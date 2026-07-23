<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\DTO\AIParseResult;
use App\DTO\AIParseResultMulti;
use App\DTO\AiProviderRequest;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Prompt\MultiTransactionPromptBuilder;
use App\Services\AI\Prompt\TransactionPromptBuilder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiProvider implements AIProviderInterface
{
    public function __construct(
        private readonly TransactionPromptBuilder $promptBuilder,
        private readonly MultiTransactionPromptBuilder $multiPromptBuilder,
    ) {}

    public function parseTransaction(AiProviderRequest $request): AIParseResult
    {
        try {
            $prompt = $this->promptBuilder->build(
                $request->text,
                $request->wallets,
                $request->categories,
                $request->activeMemories
            );

            $response = $this->sendRequest($request->model, $request->apiKey, $prompt);

            $this->assertSuccessful($response, 'Gemini');

            $jsonString = $response->json('candidates.0.content.parts.0.text');
            $aiRaw = $this->decodeJson((string) $jsonString, 'Gemini');

            if (isset($aiRaw['is_transaction']) && $aiRaw['is_transaction'] === false) {
                return AIParseResult::failure(
                    $aiRaw['reply_message'] ?? 'Maaf Bos, saya hanya bisa membantu mencatat keuangan di Bendaharaku.',
                    'gemini',
                    $request->model
                );
            }

            if (! isset($aiRaw['amount'])) {
                throw new AiProviderException('Gemini', 'Response tidak mengandung format transaksi yang valid.');
            }

            $usage = [
                'prompt' => $response->json('usageMetadata.promptTokenCount') ?? 0,
                'completion' => $response->json('usageMetadata.candidatesTokenCount') ?? 0,
                'total' => $response->json('usageMetadata.totalTokenCount') ?? 0,
            ];

            return new AIParseResult(
                success: true,
                confidence: (float) ($aiRaw['confidence'] ?? 0.85),
                error: null,
                transaction: $this->buildParsedTransaction($aiRaw, $request->text),
                usage: $usage,
                provider: 'gemini',
                model: $request->model,
            );

        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::warning('Gemini Connection Timeout', ['message' => $e->getMessage()]);
            throw new AiTimeoutException('Gemini');
        } catch (Throwable $e) {
            Log::error('Gemini Provider Error', ['message' => $e->getMessage(), 'text' => $request->text]);
            throw new AiProviderException('Gemini', $e->getMessage());
        }
    }

    public function parseMultiTransaction(AiProviderRequest $request): AIParseResultMulti
    {
        try {
            $prompt = $this->multiPromptBuilder->build(
                $request->text,
                $request->wallets,
                $request->categories,
                $request->activeMemories
            );

            $response = $this->sendRequest($request->model, $request->apiKey, $prompt);

            $this->assertSuccessful($response, 'Gemini');

            $jsonString = $response->json('candidates.0.content.parts.0.text');
            $aiRaw = $this->decodeJson((string) $jsonString, 'Gemini');

            if (isset($aiRaw['is_transaction']) && $aiRaw['is_transaction'] === false) {
                return new AIParseResultMulti(
                    success: false,
                    transactions: [],
                    confidence: 0.0,
                    error: $aiRaw['reply_message'] ?? 'Maaf Bos, saya hanya bisa membantu mencatat keuangan di Bendaharaku.',
                    isOutOfScope: true,
                    replyMessage: $aiRaw['reply_message'] ?? null,
                    usage: [],
                    provider: 'gemini',
                    model: $request->model,
                );
            }

            if (! isset($aiRaw['transactions']) || ! is_array($aiRaw['transactions'])) {
                throw new AiProviderException('Gemini', 'Response multi-transaksi tidak mengandung array "transactions".');
            }

            $usage = [
                'prompt' => $response->json('usageMetadata.promptTokenCount') ?? 0,
                'completion' => $response->json('usageMetadata.candidatesTokenCount') ?? 0,
                'total' => $response->json('usageMetadata.totalTokenCount') ?? 0,
            ];

            $transactions = $this->buildParsedTransactions($aiRaw['transactions'], $request->text);
            $avgConfidence = $this->averageConfidence($aiRaw['transactions']);

            return new AIParseResultMulti(
                success: true,
                transactions: $transactions,
                confidence: $avgConfidence,
                error: null,
                usage: $usage,
                provider: 'gemini',
                model: $request->model,
            );

        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::warning('Gemini Multi Connection Timeout', ['message' => $e->getMessage()]);
            throw new AiTimeoutException('Gemini');
        } catch (Throwable $e) {
            Log::error('Gemini Multi Provider Error', ['message' => $e->getMessage(), 'text' => $request->text]);
            throw new AiProviderException('Gemini', $e->getMessage());
        }
    }

    // ── Shared Helpers ────────────────────────────────────────────────

    /**
     * Sentralisasi eksekusi HTTP Request untuk Gemini.
     */
    private function sendRequest(string $model, string $apiKey, string $prompt): Response
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $connectTimeout = (int) config('services.gemini.connect_timeout', 10);
        $timeout = (int) config('services.gemini.timeout', 30);

        $start = microtime(true);
        $status = null;

        try {
            $response = Http::connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->retry(
                    2,
                    function (int $attempt) {
                        $delayMs = 2000 * (2 ** ($attempt - 1));

                        Log::warning('Gemini Connection Retrying', [
                            'attempt' => $attempt,
                            'delay_ms' => $delayMs,
                        ]);

                        return $delayMs;
                    },
                    function (Throwable $exception) {
                        return $exception instanceof ConnectionException;
                    }
                )
                ->post($url.'?key='.$apiKey, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                ]);

            $status = $response->status();

            return $response;
        } finally {
            Log::info('Gemini Request Finished', [
                'provider' => 'gemini',
                'model' => $model,
                'status' => $status,
                'success' => $status !== null && $status >= 200 && $status < 300,
                'timeout' => $timeout,
                'elapsed_ms' => round((microtime(true) - $start) * 1000),
            ]);
        }
    }

    private function assertSuccessful(Response $response, string $provider): void
    {
        if ($response->successful()) {
            return;
        }

        $statusCode = $response->status();

        if ($statusCode === 429) {
            throw new AiRateLimitException($provider);
        }
        if (in_array($statusCode, [408, 503, 504])) {
            throw new AiTimeoutException($provider);
        }
        if (in_array($statusCode, [401, 403])) {
            throw new AiProviderException($provider, "API Key tidak valid (HTTP {$statusCode}).");
        }

        throw new AiProviderException($provider, "HTTP {$statusCode}: ".substr($response->body(), 0, 200));
    }

    private function decodeJson(string $jsonString, string $provider): array
    {
        // Menggunakan regex multiline yang lebih bersih dan aman
        $clean = trim(preg_replace('/^```json\s*|\s*```$/im', '', $jsonString));
        $data = json_decode($clean, true);

        if (! is_array($data)) {
            throw new AiProviderException($provider, 'Gagal decode JSON dari response LLM.');
        }

        return $data;
    }

    private function buildParsedTransaction(array $raw, string $fallbackNotes): ParsedTransaction
    {
        $intentString = $raw['transactionType'] ?? null;

        return new ParsedTransaction(
            amount: (float) ($raw['amount'] ?? 0),
            transactionType: $intentString ? TransactionIntent::tryFrom(strtolower(trim($intentString))) : null,
            category: $raw['category'] ?? null,
            sourceWallet: $raw['sourceWallet'] ?? null,
            destinationWallet: $raw['destinationWallet'] ?? null,
            subject: $raw['subject'] ?? null,
            notes: $raw['notes'] ?? $fallbackNotes,
            isCleared: (bool) ($raw['isCleared'] ?? true),
            useAllBalance: (bool) ($raw['use_all_balance'] ?? false),
        );
    }

    /** @return ParsedTransaction[] */
    private function buildParsedTransactions(array $items, string $fallbackNotes): array
    {
        return array_values(array_map(
            fn (array $item) => $this->buildParsedTransaction($item, $fallbackNotes),
            $items
        ));
    }

    private function averageConfidence(array $items): float
    {
        if (empty($items)) {
            return 0.0;
        }
        $total = array_sum(array_map(fn ($i) => (float) ($i['confidence'] ?? 0.85), $items));

        return round($total / count($items), 4);
    }
}
