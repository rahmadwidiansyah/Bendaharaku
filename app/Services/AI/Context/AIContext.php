<?php

declare(strict_types=1);

namespace App\Services\AI\Context;

readonly class AIContext
{
    public function __construct(
        public string $userInput,
        public ?string $conversationId,
        public array $wallets,
        public array $categories,
        public array $keywordAliases,
        public array $activeMemories,
        public string $today,
        public string $timezone,
        public string $locale,
        public array $metadata = [],
    ) {}
}
