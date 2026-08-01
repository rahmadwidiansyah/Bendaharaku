<?php

declare(strict_types=1);

namespace App\Chat\Services;

use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiTokenLimitException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiReportClient
{
    public function sendPrompt(string $prompt, string $apiKey, string $model): ?string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $maxAttempts = 3;
        $attempt = 0;
        $response = null;

        while ($attempt < $maxAttempts) {
            $attempt++;
            $start = microtime(true);
            try {
                $response = Http::timeout(60)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url.'?key='.$apiKey, [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                    ]);

                $elapsedMs = (int) round((microtime(true) - $start) * 1000);
                Log::info('Gemini request attempt', [
                    'model' => $model,
                    'attempt' => $attempt,
                    'elapsed_ms' => $elapsedMs,
                    'status' => $response->status(),
                ]);

                if ($response->successful()) {
                    break;
                }
            } catch (Throwable $e) {
                $elapsedMs = (int) round((microtime(true) - $start) * 1000);
                Log::warning('Gemini request exception', [
                    'model' => $model,
                    'attempt' => $attempt,
                    'elapsed_ms' => $elapsedMs,
                    'exception' => $e->getMessage(),
                ]);
                $response = null;
            }

            if ($attempt < $maxAttempts) {
                sleep(1);
            }
        }

        if ($response === null) {
            Log::warning('Gemini all attempts failed', ['model' => $model]);

            return null;
        }

        $this->assertSuccessful($response, 'Gemini');

        $text = trim((string) $response->json('candidates.0.content.parts.0.text'));

        return $text !== '' ? $text : null;
    }

    private function assertSuccessful(Response $response, string $provider): void
    {
        if ($response->successful()) {
            return;
        }

        $statusCode = $response->status();
        $body = $response->body();
        $bodyLower = mb_strtolower($body);

        if ($statusCode === 429) {
            throw new AiRateLimitException($provider);
        }

        if (in_array($statusCode, [408, 503, 504])) {
            throw new AiTimeoutException($provider);
        }

        if (in_array($statusCode, [401, 403])) {
            throw new AiProviderException($provider, "API Key tidak valid (HTTP {$statusCode}).");
        }

        if ($statusCode === 400) {
            if (str_contains($bodyLower, 'token')
                || str_contains($bodyLower, 'context')
                || str_contains($bodyLower, 'input too long')
                || str_contains($bodyLower, 'exceeded')
                || str_contains($bodyLower, 'maximum')) {
                throw new AiTokenLimitException($provider, 0, $body);
            }
        }

        throw new AiProviderException($provider, "HTTP {$statusCode}: ".substr($body, 0, 200));
    }
}
