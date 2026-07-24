<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\DTO\AIParseResult;
use App\DTO\AIParseResultMulti;
use App\DTO\AiProviderRequest;
use App\Services\AI\Adapters\DeepSeekAdapter;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Prompt\MultiTransactionPromptBuilder;
use App\Services\AI\Prompt\TransactionPromptBuilder;

class DeepSeekProvider implements AIProviderInterface
{
    public function __construct(
        private readonly DeepSeekAdapter $adapter,
        private readonly TransactionPromptBuilder $promptBuilder,
        private readonly MultiTransactionPromptBuilder $multiPromptBuilder,
    ) {}

    public function parseTransaction(AiProviderRequest $request): AIParseResult
    {
        $prompt = $request->prompt ?? $this->promptBuilder->build(
            $request->text,
            $request->wallets,
            $request->categories,
            $request->activeMemories
        );

        return $this->adapter->parseTransaction(
            prompt: $prompt,
            apiKey: $request->apiKey,
            model: $request->model,
            fallbackText: $request->text,
        );
    }

    public function parseMultiTransaction(AiProviderRequest $request): AIParseResultMulti
    {
        $prompt = $request->prompt ?? $this->multiPromptBuilder->build(
            $request->text,
            $request->wallets,
            $request->categories,
            $request->activeMemories
        );

        return $this->adapter->parseMultiTransaction(
            prompt: $prompt,
            apiKey: $request->apiKey,
            model: $request->model,
            fallbackText: $request->text,
        );
    }
}
