<?php

declare(strict_types=1);

namespace App\Services\AI\Adapters;

use App\DTO\AIParseResult;
use App\DTO\AIParseResultMulti;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Services\AI\Adapters\Contracts\LLMAdapterInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseAdapter implements LLMAdapterInterface
{
    abstract protected function getProviderName(): string;

    abstract protected function getBaseUrl(string $model): string;

    abstract protected function buildRequestBody(string $model, string $prompt): array;

    abstract protected function extractResponseContent(array $responseData): ?string;

    abstract protected function extractUsage(array $responseData): array;

    protected function getSingleTimeout(): int
    {
        return 15;
    }

    protected function getMultiTimeout(): int
    {
        return 20;
    }

    protected function sendRequest(string $url, string $apiKey, array $body, int $timeout): Response
    {
        return Http::timeout($timeout)
            ->withToken($apiKey)
            ->post($url, $body);
    }

    public function parseTransaction(
        string $prompt,
        string $apiKey,
        string $model,
        string $fallbackText = '',
    ): AIParseResult {
        try {
            $url = $this->getBaseUrl($model);
            $body = $this->buildRequestBody($model, $prompt);

            $response = $this->sendRequest($url, $apiKey, $body, $this->getSingleTimeout());

            $this->assertSuccessful($response);

            $responseData = $response->json() ?? [];
            $jsonString = $this->extractResponseContent($responseData);
            $aiRaw = $this->decodeJson((string) $jsonString);

            if (isset($aiRaw['is_transaction']) && $aiRaw['is_transaction'] === false) {
                return AIParseResult::failure(
                    $aiRaw['reply_message'] ?? 'Maaf Bos, saya hanya bisa membantu mencatat keuangan di Bendaharaku.',
                    $this->getProviderName(),
                    $model,
                );
            }

            if (! isset($aiRaw['amount'])) {
                throw new AiProviderException($this->getProviderName(), 'Response tidak mengandung format transaksi yang valid.');
            }

            return new AIParseResult(
                success: true,
                confidence: (float) ($aiRaw['confidence'] ?? 0.85),
                error: null,
                transaction: $this->buildParsedTransaction($aiRaw, $fallbackText),
                usage: $this->extractUsage($responseData),
                provider: $this->getProviderName(),
                model: $model,
            );

        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::warning($this->getProviderName().' Connection Timeout', ['message' => $e->getMessage()]);
            throw new AiTimeoutException($this->getProviderName());
        } catch (Throwable $e) {
            Log::error($this->getProviderName().' Provider Error', ['message' => $e->getMessage()]);
            throw new AiProviderException($this->getProviderName(), $e->getMessage());
        }
    }

    public function parseMultiTransaction(
        string $prompt,
        string $apiKey,
        string $model,
        string $fallbackText = '',
    ): AIParseResultMulti {
        try {
            $url = $this->getBaseUrl($model);
            $body = $this->buildRequestBody($model, $prompt);
            $headers = ['Authorization' => 'Bearer '.$apiKey];

            $response = $this->sendRequest($url, $apiKey, $body, $this->getMultiTimeout());

            $this->assertSuccessful($response);

            $responseData = $response->json() ?? [];
            $jsonString = $this->extractResponseContent($responseData);
            $aiRaw = $this->decodeJson((string) $jsonString);

            if (isset($aiRaw['is_transaction']) && $aiRaw['is_transaction'] === false) {
                return new AIParseResultMulti(
                    success: false,
                    transactions: [],
                    confidence: 0.0,
                    error: $aiRaw['reply_message'] ?? 'Maaf Bos, saya hanya bisa membantu mencatat keuangan di Bendaharaku.',
                    isOutOfScope: true,
                    replyMessage: $aiRaw['reply_message'] ?? null,
                    usage: [],
                    provider: $this->getProviderName(),
                    model: $model,
                );
            }

            if (! isset($aiRaw['transactions']) || ! is_array($aiRaw['transactions'])) {
                throw new AiProviderException($this->getProviderName(), 'Response multi-transaksi tidak mengandung array "transactions".');
            }

            return new AIParseResultMulti(
                success: true,
                transactions: $this->buildParsedTransactions($aiRaw['transactions'], $fallbackText),
                confidence: $this->averageConfidence($aiRaw['transactions']),
                error: null,
                usage: $this->extractUsage($responseData),
                provider: $this->getProviderName(),
                model: $model,
            );

        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::warning($this->getProviderName().' Multi Connection Timeout', ['message' => $e->getMessage()]);
            throw new AiTimeoutException($this->getProviderName());
        } catch (Throwable $e) {
            Log::error($this->getProviderName().' Multi Provider Error', ['message' => $e->getMessage()]);
            throw new AiProviderException($this->getProviderName(), $e->getMessage());
        }
    }

    // ── Shared Helpers ────────────────────────────────────────────────

    protected function assertSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $statusCode = $response->status();
        $provider = $this->getProviderName();

        if ($statusCode === 429) {
            throw new AiRateLimitException($provider);
        }
        if (in_array($statusCode, [408, 503, 504])) {
            throw new AiTimeoutException($provider);
        }
        if (in_array($statusCode, [401, 403])) {
            throw new AiProviderException($provider, "API Key tidak valid (HTTP {$statusCode}).");
        }

        throw new AiProviderException($provider, 'HTTP '.$statusCode.': '.substr($response->body(), 0, 200));
    }

    protected function decodeJson(string $jsonString): array
    {
        $clean = trim(preg_replace('/^```json\s*|\s*```$/im', '', $jsonString));
        $data = json_decode($clean, true);

        if (! is_array($data)) {
            throw new AiProviderException($this->getProviderName(), 'Gagal decode JSON dari response LLM.');
        }

        return $data;
    }

    protected function buildParsedTransaction(array $raw, string $fallbackNotes): ParsedTransaction
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
    protected function buildParsedTransactions(array $items, string $fallbackNotes): array
    {
        return array_values(array_map(
            fn (array $item) => $this->buildParsedTransaction($item, $fallbackNotes),
            $items
        ));
    }

    protected function averageConfidence(array $items): float
    {
        if (empty($items)) {
            return 0.0;
        }

        $total = array_sum(array_map(fn ($i) => (float) ($i['confidence'] ?? 0.85), $items));

        return round($total / count($items), 4);
    }
}
