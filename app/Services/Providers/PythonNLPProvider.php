<?php

declare(strict_types=1);

namespace App\Services\Providers;

use App\DTO\AIParseResult;
use App\DTO\AiProviderRequest;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Exception;

class PythonNLPProvider implements AIProviderInterface
{
    public function parseTransaction(AiProviderRequest $request): AIParseResult
    {
        $url = config('services.python_ai.url', env('PYTHON_AI_URL'));
        $apiKey = config('services.python_ai.key', env('PYTHON_AI_KEY'));
        
        if (blank($url)) {
            return AIParseResult::failure("Konfigurasi API Python NLP Service belum diset di .env");
        }

        try {
            $response = Http::withHeaders(['X-API-KEY' => $apiKey])
                ->timeout(10)
                ->post($url . '/analyze', [
                    'text' => $request->text,
                    'wallets' => $request->wallets,
                    'categories' => $request->categories
                ]);

            if (!$response->successful()) {
                return AIParseResult::failure("Python NLP Service merespon dengan HTTP status: " . $response->status());
            }

            $aiRaw = $response->json();

            if (!isset($aiRaw['amount'])) {
                return AIParseResult::failure("Respons tidak valid: AI gagal mengekstrak nominal angka transaksi.");
            }

            $intentString = $aiRaw['transaction_type'] ?? null;
            $transactionIntent = $intentString ? TransactionIntent::tryFrom(strtolower(trim($intentString))) : null;

            $parsedTransaction = new ParsedTransaction(
                amount: (float) $aiRaw['amount'],
                transactionType: $transactionIntent,
                category: $aiRaw['category'] ?? null,
                sourceWallet: $aiRaw['source_wallet'] ?? null,
                destinationWallet: $aiRaw['destination_wallet'] ?? null,
                subject: $aiRaw['subject'] ?? null,
                notes: $aiRaw['notes'] ?? $request->text,
                isCleared: (bool) ($aiRaw['is_cleared'] ?? true)
            );

            return new AIParseResult(
                success: true,
                confidence: (float) ($aiRaw['confidence'] ?? 0.0),
                error: null,
                transaction: $parsedTransaction
            );

        } catch (ConnectionException $e) {
            return AIParseResult::failure("Gagal terhubung ke Python NLP Service (Timeout atau Server Down).");
        } catch (Exception $e) {
            return AIParseResult::failure("Kesalahan internal saat menerjemahkan output AI: " . $e->getMessage());
        }
    }
}