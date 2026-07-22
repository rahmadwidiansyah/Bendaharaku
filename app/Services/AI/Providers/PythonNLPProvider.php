<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\DTO\AIParseResult;
use App\DTO\AIParseResultMulti;
use App\DTO\AiProviderRequest;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PythonNLPProvider implements AIProviderInterface
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ai_parser.url', 'http://ai-parser:3987'), '/');
    }

    public function parseTransaction(AiProviderRequest $request): AIParseResult
    {
        if (blank($this->baseUrl)) {
            Log::warning('PythonNLPProvider: AI_PARSER_URL tidak dikonfigurasi, skip.');

            return AIParseResult::failure('AI Parser tidak dikonfigurasi.', 'python-nlp', 'local');
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl.'/analyze', [
                    'text' => $request->text,
                    'wallets' => $request->wallets,
                    'categories' => $request->categories,
                ]);

            if (! $response->successful()) {
                Log::warning('PythonNLPProvider: HTTP Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return AIParseResult::failure(
                    "AI Parser Error ({$response->status()}): {$response->body()}",
                    'python-nlp',
                    'local'
                );
            }

            $data = $response->json();

            if (empty($data['success']) || ! isset($data['amount']) || $data['amount'] === null) {
                return AIParseResult::failure(
                    'AI Parser: Response tidak valid atau amount kosong.',
                    'python-nlp',
                    'local'
                );
            }

            $intentString = strtolower(trim($data['transaction_type'] ?? 'expense'));
            $transactionIntent = TransactionIntent::tryFrom($intentString) ?? TransactionIntent::Expense;

            $parsedTransaction = new ParsedTransaction(
                amount: (float) $data['amount'],
                transactionType: $transactionIntent,
                category: $data['category'] ?? null,
                sourceWallet: $data['source_wallet'] ?? null,
                destinationWallet: $data['destination_wallet'] ?? null,
                subject: $data['subject'] ?? null,
                notes: $data['notes'] ?? $request->text,
                isCleared: (bool) ($data['is_cleared'] ?? false),
            );

            $confidence = min(1.0, max(0.0, (float) ($data['confidence'] ?? 0.0)));

            return new AIParseResult(
                success: true,
                confidence: $confidence,
                error: null,
                transaction: $parsedTransaction,
                usage: ['prompt' => 0, 'completion' => 0, 'total' => 0],
                provider: 'python-nlp',
                model: 'local',
            );

        } catch (ConnectionException $e) {
            Log::warning('PythonNLPProvider: Connection timeout/refused', [
                'url' => $this->baseUrl,
                'message' => $e->getMessage(),
            ]);

            return AIParseResult::failure('AI Parser timeout/tidak terjangkau.', 'python-nlp', 'local');
        } catch (Throwable $e) {
            Log::error('PythonNLPProvider: Unexpected error', [
                'message' => $e->getMessage(),
                'text' => $request->text,
            ]);

            return AIParseResult::failure('AI Parser error: '.$e->getMessage(), 'python-nlp', 'local');
        }
    }

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
