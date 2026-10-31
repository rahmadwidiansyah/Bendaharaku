<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * AgentTurn — hasil satu panggilan LLM pada agent loop.
 *
 * toolCalls: list<array{id:string,name:string,arguments:array<string,mixed>}>
 */
readonly class AgentTurn
{
    /**
     * @param  array<int, array{id:string,name:string,arguments:array<string,mixed>}>  $toolCalls
     * @param  array{prompt:int,completion:int,total:int}  $usage
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public ?string $content = null,
        public array $toolCalls = [],
        public array $usage = [],
        public ?string $finishReason = null,
        public array $raw = [],
    ) {}

    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== [];
    }
}
