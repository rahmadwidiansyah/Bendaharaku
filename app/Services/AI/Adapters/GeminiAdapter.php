<?php

declare(strict_types=1);

namespace App\Services\AI\Adapters;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class GeminiAdapter extends BaseAdapter
{
    protected function getProviderName(): string
    {
        return 'gemini';
    }

    protected function getBaseUrl(string $model): string
    {
        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
    }

    protected function buildRequestBody(string $model, string $prompt): array
    {
        return [
            'contents' => [['parts' => [['text' => $prompt]]]],
        ];
    }

    protected function sendRequest(string $url, string $apiKey, array $body, int $timeout): Response
    {
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
                    },
                    false
                )
                ->post($url.'?key='.$apiKey, $body);

            $status = $response->status();
            return $response;
        } finally {
            Log::info('Gemini Request Finished', [
                'provider' => 'gemini',
                'model' => null,
                'status' => $status,
                'success' => $status !== null && $status >= 200 && $status < 300,
                'timeout' => $timeout,
                'elapsed_ms' => round((microtime(true) - $start) * 1000),
            ]);
        }
    }

    protected function extractResponseContent(array $responseData): ?string
    {
        return $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    protected function extractUsage(array $responseData): array
    {
        $usage = $responseData['usageMetadata'] ?? [];
        $prompt = (int) ($usage['promptTokenCount'] ?? 0);
        $completion = (int) ($usage['candidatesTokenCount'] ?? 0);

        return [
            'prompt' => $prompt,
            'completion' => $completion,
            'total' => $usage['totalTokenCount'] ?? ($prompt + $completion),
        ];
    }
}
