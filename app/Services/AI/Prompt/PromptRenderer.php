<?php

declare(strict_types=1);

namespace App\Services\AI\Prompt;

use App\Services\AI\Context\AIContext;

class PromptRenderer
{
    private const VARIABLE_MAP = [
        '{{SCOPE_RULE}}' => PromptInstructions::SCOPE_RULE,
        '{{WALLET_NULL_RULE}}' => PromptInstructions::WALLET_NULL_RULE,
        '{{AMOUNT_RULE}}' => PromptInstructions::AMOUNT_RULE,
        '{{AMOUNT_SHORTHAND}}' => PromptInstructions::AMOUNT_SHORTHAND,
    ];

    public function renderSingle(AIContext $context): string
    {
        $instruction = $this->loadTemplate('transaction-single');
        $instruction = str_replace(
            array_keys(self::VARIABLE_MAP),
            array_values(self::VARIABLE_MAP),
            $instruction
        );

        $payload = [
            'instruction' => $instruction,
            'text' => $context->userInput,
            'available_wallets' => $context->wallets,
        ];

        // Transfer and out-of-scope don't need categories
        $payload['available_categories'] = $context->categories;

        if (! empty($context->activeMemories)) {
            $payload['user_historical_patterns'] = array_map(
                fn (array $m) => ['keyword' => $m['keyword'], 'target_category' => $m['category']],
                $context->activeMemories,
            );
        }

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function renderMulti(AIContext $context): string
    {
        $instruction = $this->loadTemplate('transaction-multi');
        $instruction = str_replace(
            array_keys(self::VARIABLE_MAP),
            array_values(self::VARIABLE_MAP),
            $instruction
        );

        $historicalMappings = array_map(
            fn (array $m) => ['keyword' => $m['keyword'], 'target_category' => $m['category']],
            $context->activeMemories,
        );

        $payload = [
            'instruction' => $instruction,
            'text' => $context->userInput,
            'available_wallets' => $context->wallets,
            'available_categories' => $context->categories,
            'wallet_keyword_aliases' => $context->keywordAliases,
            'category_keyword_aliases' => $context->keywordAliases,
            'historical_patterns' => $historicalMappings,
            'response_format' => '{"transactions":[{...},{...}]}',
        ];

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function loadTemplate(string $name): string
    {
        $path = __DIR__.'/Templates/'.$name.'.prompt.md';

        return file_get_contents($path);
    }
}
