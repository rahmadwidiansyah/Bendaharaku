<?php

declare(strict_types=1);

namespace App\Services\AI\Adapters\Contracts;

use App\DTO\AgentRequest;
use App\DTO\AgentTurn;
use App\DTO\AIParseResult;
use App\DTO\AIParseResultMulti;

interface LLMAdapterInterface
{
    public function generateText(
        string $prompt,
        string $apiKey,
        string $model,
        ?int $timeoutSeconds = null,
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

    /**
     * Satu turn agent dengan dukungan tool-calling.
     *
     * Request/response memakai bentuk provider-agnostic (AgentRequest/AgentTurn);
     * adapter bertanggung jawab memetakan ke format provider masing-masing.
     */
    public function chatWithTools(AgentRequest $request): AgentTurn;
}
