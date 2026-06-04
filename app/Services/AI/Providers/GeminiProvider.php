<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Prompt\TransactionPromptBuilder;
use App\DTO\AIParseResult;
use App\DTO\AiProviderRequest;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
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
            $prompt = $this->promptBuilder->build($request->text, $request->wallets, $request->categories);

            $response = Http::timeout(15)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url . '?key=' . $request->apiKey, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ]
            ]);

            if (!$response->successful()) {
                $statusCode = $response->status();
                
                if ($statusCode === 429) {
                    return AIParseResult::failure("❌ Token API Gemini habis atau menyentuh limit. Silakan cek kuota di dashboard Google AI Studio Anda.");
                }
                if (in_array($statusCode, [408, 503, 504])) {
                    return AIParseResult::failure("⏳ Server Gemini sedang sibuk atau Timeout. Coba lagi dalam beberapa menit ya.");
                }
                
                return AIParseResult::failure("Gemini API Error ({$statusCode}): " . $response->body());
            }

            $jsonString = $response->json('candidates.0.content.parts.0.text');
            // Bersihkan format markdown jika AI membalas dengan ```json ... ```
            $jsonString = preg_replace('/```json\s*|\s*```/', '', (string) $jsonString);
            $aiRaw = json_decode($jsonString, true);

            if (!$aiRaw || !isset($aiRaw['amount'])) {
                return AIParseResult::failure("Gemini gagal mengekstrak format transaksi secara valid.");
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

            return new AIParseResult(true, $confidence, null, $parsedTransaction, $usage);

        } catch (ConnectionException $e) {
            Log::error('Gemini Connection Timeout', ['exception' => $e, 'message' => $e->getMessage()]);
            return AIParseResult::failure("⏳ Waktu request ke API Gemini habis (Timeout). Coba lagi nanti Bos.");
        } catch (Throwable $e) {
            Log::error('Gemini Provider Error', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'text' => $request->text
            ]);
            return AIParseResult::failure("Kesalahan internal Gemini Provider: " . $e->getMessage());
        }
    }
}