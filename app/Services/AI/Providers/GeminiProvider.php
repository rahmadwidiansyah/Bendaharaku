<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Prompt\TransactionPromptBuilder;
use App\Services\AI\Prompt\MultiTransactionPromptBuilder;
use App\DTO\AIParseResult;
use App\DTO\AIParseResultMulti;
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
        private readonly TransactionPromptBuilder $promptBuilder,
        private readonly MultiTransactionPromptBuilder $multiPromptBuilder,
    ) {}

    public function parseTransaction(AiProviderRequest $request): AIParseResult
    {
        try {
            $url    = "https://generativelanguage.googleapis.com/v1beta/models/{$request->model}:generateContent";
            $prompt = $this->promptBuilder->build(
                $request->text,
                $request->wallets,
                $request->categories,
                $request->activeMemories
            );

            $response = Http::timeout(15)
                ->retry(2, 1000)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url . '?key=' . $request->apiKey, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                ]);

            $this->assertSuccessful($response, 'Gemini');

            $jsonString = $response->json('candidates.0.content.parts.0.text');
            $aiRaw      = $this->decodeJson((string) $jsonString, 'Gemini');

            if (!isset($aiRaw['amount'])) {
                throw new AiProviderException('Gemini', 'Response tidak mengandung format transaksi yang valid.');
            }

            $usage = [
                'prompt'     => $response->json('usageMetadata.promptTokenCount') ?? 0,
                'completion' => $response->json('usageMetadata.candidatesTokenCount') ?? 0,
                'total'      => $response->json('usageMetadata.totalTokenCount') ?? 0,
            ];

            return new AIParseResult(
                success:     true,
                confidence:  (float) ($aiRaw['confidence'] ?? 0.85),
                error:       null,
                transaction: $this->buildParsedTransaction($aiRaw, $request->text),
                usage:       $usage,
                provider:    'gemini',
                model:       $request->model,
            );

        } catch (AiRateLimitException | AiTimeoutException | AiProviderException $e) {
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
            $url    = "https://generativelanguage.googleapis.com/v1beta/models/{$request->model}:generateContent";
            $prompt = $this->multiPromptBuilder->build(
                $request->text,
                $request->wallets,
                $request->categories,
                $request->activeMemories
            );

            $response = Http::timeout(20)
                ->retry(2, 1000)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url . '?key=' . $request->apiKey, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                ]);

            $this->assertSuccessful($response, 'Gemini');

            $jsonString = $response->json('candidates.0.content.parts.0.text');
            $aiRaw      = $this->decodeJson((string) $jsonString, 'Gemini');

            if (!isset($aiRaw['transactions']) || !is_array($aiRaw['transactions'])) {
                throw new AiProviderException('Gemini', 'Response multi-transaksi tidak mengandung array "transactions".');
            }

            $usage = [
                'prompt'     => $response->json('usageMetadata.promptTokenCount') ?? 0,
                'completion' => $response->json('usageMetadata.candidatesTokenCount') ?? 0,
                'total'      => $response->json('usageMetadata.totalTokenCount') ?? 0,
            ];

            $transactions = $this->buildParsedTransactions($aiRaw['transactions'], $request->text);
            $avgConfidence = $this->averageConfidence($aiRaw['transactions']);

            return new AIParseResultMulti(
                success:      true,
                transactions: $transactions,
                confidence:   $avgConfidence,
                error:        null,
                usage:        $usage,
                provider:     'gemini',
                model:        $request->model,
            );

        } catch (AiRateLimitException | AiTimeoutException | AiProviderException $e) {
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

    private function assertSuccessful(\Illuminate\Http\Client\Response $response, string $provider): void
    {
        if ($response->successful()) return;
        $statusCode = $response->status();
        if ($statusCode === 429)                        throw new AiRateLimitException($provider);
        if (in_array($statusCode, [408, 503, 504]))    throw new AiTimeoutException($provider);
        if (in_array($statusCode, [401, 403]))         throw new AiProviderException($provider, "API Key tidak valid (HTTP {$statusCode}).");
        throw new AiProviderException($provider, "HTTP {$statusCode}: " . substr($response->body(), 0, 200));
    }

    private function decodeJson(string $jsonString, string $provider): array
    {
        $clean = preg_replace('/```json\s*|\s*```/', '', $jsonString);
        $data  = json_decode($clean, true);
        if (!is_array($data)) {
            throw new AiProviderException($provider, 'Gagal decode JSON dari response LLM.');
        }
        return $data;
    }

    private function buildParsedTransaction(array $raw, string $fallbackNotes): ParsedTransaction
    {
        $intentString = $raw['transactionType'] ?? null;
        return new ParsedTransaction(
            amount:           (float) ($raw['amount'] ?? 0),
            transactionType:  $intentString ? TransactionIntent::tryFrom(strtolower(trim($intentString))) : null,
            category:         $raw['category'] ?? null,
            sourceWallet:     $raw['sourceWallet'] ?? null,
            destinationWallet: $raw['destinationWallet'] ?? null,
            subject:          $raw['subject'] ?? null,
            notes:            $raw['notes'] ?? $fallbackNotes,
            isCleared:        (bool) ($raw['isCleared'] ?? true),
        );
    }

    /** @return ParsedTransaction[] */
    private function buildParsedTransactions(array $items, string $fallbackNotes): array
    {
        return array_values(array_map(
            fn(array $item) => $this->buildParsedTransaction($item, $fallbackNotes),
            $items
        ));
    }

    private function averageConfidence(array $items): float
    {
        if (empty($items)) return 0.0;
        $total = array_sum(array_map(fn($i) => (float) ($i['confidence'] ?? 0.85), $items));
        return round($total / count($items), 4);
    }
}
