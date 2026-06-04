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

class DeepSeekProvider implements AIProviderInterface
{
    public function __construct(
        private readonly TransactionPromptBuilder $promptBuilder
    ) {}

    public function parseTransaction(AiProviderRequest $request): AIParseResult
    {
        try {
            $url = "https://api.deepseek.com/chat/completions";
            $prompt = $this->promptBuilder->build($request->text, $request->wallets, $request->categories);

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
                    return AIParseResult::failure("❌ Saldo token DeepSeek Anda habis. Silakan cek dashboard DeepSeek Platform Anda.");
                }
                if (in_array($statusCode, [408, 503, 504])) {
                    return AIParseResult::failure("⏳ Server DeepSeek sedang sibuk (Timeout). Coba lagi nanti.");
                }
                
                return AIParseResult::failure("DeepSeek API Error ({$statusCode}): " . $response->body());
            }

            $jsonString = $response->json('choices.0.message.content');
            // Bersihkan format markdown jika AI membalas dengan ```json ... ```
            $jsonString = preg_replace('/```json\s*|\s*```/', '', (string) $jsonString);
            $aiRaw = json_decode($jsonString, true);

            if (!$aiRaw || !isset($aiRaw['amount'])) {
                return AIParseResult::failure("DeepSeek gagal mengekstrak format transaksi secara valid.");
            }

            // Ekstrak Confidence
            $confidence = isset($aiRaw['confidence']) ? (float) $aiRaw['confidence'] : 0.85;

            // Ekstrak Token Usage DeepSeek
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

            return new AIParseResult(true, $confidence, null, $parsedTransaction, $usage);

        } catch (ConnectionException $e) {
            Log::error('DeepSeek Connection Timeout', ['exception' => $e, 'message' => $e->getMessage()]);
            return AIParseResult::failure("⏳ Waktu request ke API DeepSeek habis (Timeout). Coba lagi nanti Bos.");
        } catch (Throwable $e) {
            Log::error('DeepSeek Provider Error', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'text' => $request->text
            ]);
            return AIParseResult::failure("Kesalahan internal DeepSeek Provider: " . $e->getMessage());
        }
    }
}