<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\AiProvider;

/**
 * AgentRequest — payload provider-agnostic untuk satu turn agent.
 *
 * messages memakai bentuk netral:
 *   ['role' => 'system'|'user'|'assistant'|'tool', 'content' => ?string, ...]
 * Adapter masing-masing mengubahnya ke format provider.
 */
readonly class AgentRequest
{
    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     */
    public function __construct(
        public AiProvider $provider,
        public string $model,
        public string $apiKey,
        public array $messages,
        public array $tools = [],
        public string $botName = 'Ken-Chan',
    ) {}
}
