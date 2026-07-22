<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\AiProvider;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Providers\DeepSeekProvider;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\OpenAIProvider;

readonly class AiProviderFactory
{
    public function __construct(
        private GeminiProvider $geminiProvider,
        private OpenAIProvider $openAiProvider,
        private DeepSeekProvider $deepSeekProvider
    ) {}

    public function make(AiProvider $providerEnum): AIProviderInterface
    {
        return match ($providerEnum) {
            AiProvider::Gemini => $this->geminiProvider,
            AiProvider::OpenAI => $this->openAiProvider,
            AiProvider::DeepSeek => $this->deepSeekProvider,
        };
    }
}
