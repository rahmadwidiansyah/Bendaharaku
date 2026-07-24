<?php

declare(strict_types=1);

namespace App\Services\AI\Adapters;

class DeepSeekAdapter extends BaseAdapter
{
    protected function getProviderName(): string
    {
        return 'deepseek';
    }

    protected function getBaseUrl(string $model): string
    {
        return 'https://api.deepseek.com/chat/completions';
    }

    protected function buildRequestBody(string $model, string $prompt): array
    {
        return [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'response_format' => ['type' => 'json_object'],
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
}
