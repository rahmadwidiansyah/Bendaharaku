<?php

declare(strict_types=1);

namespace App\Services\AI\Adapters;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenAiCompatibleAdapter extends BaseAdapter
{
    protected function getProviderName(): string
    {
        return 'openai-compatible';
    }

    protected function getBaseUrl(string $model): string
    {
        return (string) config('bendaharaku.ai.openai_compatible.base_url');
    }

    protected function buildRequestBody(string $model, string $prompt): array
    {
        return [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'response_format' => ['type' => 'json_object'],
            'stream' => false,
        ];
    }

    protected function extractResponseContent(array $responseData): ?string
    {
        return $responseData['choices'][0]['message']['content'] ?? null;
    }

    protected function extractUsage(array $responseData): array
    {
        return [
            'prompt' => $responseData['usage']['prompt_tokens'] ?? 0,
            'completion' => $responseData['usage']['completion_tokens'] ?? 0,
            'total' => $responseData['usage']['total_tokens'] ?? 0,
        ];
    }

    protected function sendRequest(string $url, string $apiKey, array $body, int $timeout): Response
    {
        $request = Http::timeout($timeout)->withToken($apiKey);

        $headerName = (string) config('bendaharaku.ai.openai_compatible.secret_header_name');
        $headerValue = (string) config('bendaharaku.ai.openai_compatible.secret_header_value');

        if ($headerName !== '' && $headerValue !== '') {
            $request = $request->withHeaders([$headerName => $headerValue]);
        }

        return $request->post($url, $body);
    }
}
