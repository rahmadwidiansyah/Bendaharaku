<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\AiProvider;
use App\Services\AI\Adapters\Contracts\LLMAdapterInterface;
use App\Services\AI\Adapters\DeepSeekAdapter;
use App\Services\AI\Adapters\GeminiAdapter;
use App\Services\AI\Adapters\OpenAIAdapter;
use App\Services\AI\Adapters\OpenAiCompatibleAdapter;

// Sengaja bukan readonly: Mockery tidak bisa membuat mock subclass dari
// readonly class (fatal "cannot extend readonly class"), dan factory ini
// di-mock di ChatIntentClassifierTest / AgentLoopTest.
class AiProviderFactory
{
    public function __construct(
        private GeminiAdapter $geminiAdapter,
        private OpenAIAdapter $openAiAdapter,
        private DeepSeekAdapter $deepSeekAdapter,
        private OpenAiCompatibleAdapter $openAiCompatibleAdapter,
    ) {}

    public function make(AiProvider $providerEnum): LLMAdapterInterface
    {
        return match ($providerEnum) {
            AiProvider::Gemini => $this->geminiAdapter,
            AiProvider::OpenAI => $this->openAiAdapter,
            AiProvider::DeepSeek => $this->deepSeekAdapter,
            AiProvider::OpenAiCompatible => $this->openAiCompatibleAdapter,
        };
    }
}
