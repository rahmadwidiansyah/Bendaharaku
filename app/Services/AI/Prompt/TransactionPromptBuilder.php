<?php

declare(strict_types=1);

namespace App\Services\AI\Prompt;

class TransactionPromptBuilder
{
    public function __construct(
        private readonly ContextBuilder $contextBuilder = new ContextBuilder
    ) {}

    public function build(string $text, array $wallets, array $categories, array $activeMemories = []): string
    {
        $context = $this->contextBuilder->build($text, $wallets, $categories, $activeMemories);

        // Reserved keys must never override instruction/text
        foreach (['instruction', 'text'] as $reserved) {
            unset($context[$reserved]);
        }

        $instruction = require resource_path('prompts/transaction-single.php');

        $payload = array_merge(['instruction' => $instruction, 'text' => $text], $context);

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
