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

class OpenAIProvider implements AIProviderInterface
{
    public function __construct(
        private readonly TransactionPromptBuilder $promptBuilder,
        private readonly MultiTransactionPromptBuilder $multiPromptBuilder,
    ) {}

    public function parseTransaction(AiProviderRequest $request): AIParseResult
    {
        try {
            $url = 'https://api.openai.com/v1/chat/completions';
            $prompt = $this->promptBuilder->build(
                $request->text,
                $request->wallets,
                $request->categories,
                $request->activeMemories
            );

            $response = Http::timeout(15)->withToken($request->apiKey)
                ->post($url, [
                    'model' => $request->model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'response_format' => ['type' => 'json_object'],
                ]);

            $this->assertSuccessful($response, 'OpenAI');

            $jsonString = $response->json('choices.0.message.content');
            $aiRaw = $this->decodeJson((string) $jsonString, 'OpenAI');

            if (isset($aiRaw['is_transaction']) && $aiRaw['is_transaction'] === false) {
                return AIParseResult::failure(
                    $aiRaw['reply_message'] ?? 'Maaf Bos, saya hanya bisa membantu mencatat keuangan di Bendaharaku.',
                    'openai',
                    $request->model
                );
            }

            if (! isset($aiRaw['amount'])) {
                throw new AiProviderException('OpenAI', 'Response tidak mengandung format transaksi yang valid.');
            }

            $usage = [
                'prompt' => $response->json('usage.prompt_tokens') ?? 0,
                'completion' => $response->json('usage.completion_tokens') ?? 0,
                'total' => $response->json('usage.total_tokens') ?? 0,
            ];

            return new AIParseResult(
                success: true,
                confidence: (float) ($aiRaw['confidence'] ?? 0.85),
                error: null,
                transaction: $this->buildParsedTransaction($aiRaw, $request->text),
                usage: $usage,
                provider: 'openai',
                model: $request->model,
            );

        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::warning('OpenAI Connection Timeout', ['message' => $e->getMessage()]);
            throw new AiTimeoutException('OpenAI');
        } catch (Throwable $e) {
            Log::error('OpenAI Provider Error', ['message' => $e->getMessage(), 'text' => $request->text]);
            throw new AiProviderException('OpenAI', $e->getMessage());
        }
    }

    public function parseMultiTransaction(AiProviderRequest $request): AIParseResultMulti
    {
        try {
            $url = 'https://api.openai.com/v1/chat/completions';
            $prompt = $this->multiPromptBuilder->build(
                $request->text,
                $request->wallets,
                $request->categories,
                $request->activeMemories
            );

            $response = Http::timeout(20)->withToken($request->apiKey)
                ->post($url, [
                    'model' => $request->model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'response_format' => ['type' => 'json_object'],
                ]);

            $this->assertSuccessful($response, 'OpenAI');

            $jsonString = $response->json('choices.0.message.content');
            $aiRaw = $this->decodeJson((string) $jsonString, 'OpenAI');

            if (! isset($aiRaw['transactions']) || ! is_array($aiRaw['transactions'])) {
                throw new AiProviderException('OpenAI', 'Response multi-transaksi tidak mengandung array "transactions".');
            }

            $usage = [
                'prompt' => $response->json('usage.prompt_tokens') ?? 0,
                'completion' => $response->json('usage.completion_tokens') ?? 0,
                'total' => $response->json('usage.total_tokens') ?? 0,
            ];

            return new AIParseResultMulti(
                success: true,
                transactions: $this->buildParsedTransactions($aiRaw['transactions'], $request->text),
                confidence: $this->averageConfidence($aiRaw['transactions']),
                error: null,
                usage: $usage,
                provider: 'openai',
                model: $request->model,
            );

        } catch (AiRateLimitException|AiTimeoutException|AiProviderException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::warning('OpenAI Multi Connection Timeout', ['message' => $e->getMessage()]);
            throw new AiTimeoutException('OpenAI');
        } catch (Throwable $e) {
            Log::error('OpenAI Multi Provider Error', ['message' => $e->getMessage(), 'text' => $request->text]);
            throw new AiProviderException('OpenAI', $e->getMessage());
        }
    }

    // ── Shared Helpers ────────────────────────────────────────────────

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
        $clean = preg_replace('/```json\s*|\s*```/', '', $jsonString);
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
