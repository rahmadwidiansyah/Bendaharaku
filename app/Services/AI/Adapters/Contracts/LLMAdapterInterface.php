<?php

declare(strict_types=1);

namespace App\Services\AI\Adapters\Contracts;

use App\DTO\AIParseResult;
use App\DTO\AIParseResultMulti;

interface LLMAdapterInterface
{
    public function generateText(
        string $prompt,
        string $apiKey,
        string $model,
    ): string;

    public function parseTransaction(
        string $prompt,
        string $apiKey,
        string $model,
        string $fallbackText = '',
    ): AIParseResult;

    public function parseMultiTransaction(
        string $prompt,
        string $apiKey,
        string $model,
        string $fallbackText = '',
    ): AIParseResultMulti;
}
