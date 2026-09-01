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
            $categoryPatterns = [];
            $walletPatterns = [];

            foreach ($context->activeMemories as $m) {
                $entry = ['keyword' => $m['keyword']];
                if (! empty($m['category'])) {
                    $entry['target_category'] = $m['category'];
                    $categoryPatterns[] = $entry;
                }
                if (! empty($m['wallet'])) {
                    $walletEntry = ['keyword' => $m['keyword'], 'target_wallet' => $m['wallet']];
                    $walletPatterns[] = $walletEntry;
                }
            }

            if (! empty($categoryPatterns)) {
                $payload['user_category_patterns'] = $categoryPatterns;
            }
            if (! empty($walletPatterns)) {
                $payload['user_wallet_patterns'] = $walletPatterns;
            }
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

        $categoryPatterns = [];
        $walletPatterns = [];

        foreach ($context->activeMemories as $m) {
            if (! empty($m['category'])) {
                $categoryPatterns[] = ['keyword' => $m['keyword'], 'target_category' => $m['category']];
            }
            if (! empty($m['wallet'])) {
                $walletPatterns[] = ['keyword' => $m['keyword'], 'target_wallet' => $m['wallet']];
            }
        }

        $payload = [
            'instruction' => $instruction,
            'text' => $context->userInput,
            'available_wallets' => $context->wallets,
            'available_categories' => $context->categories,
            'wallet_keyword_aliases' => $context->keywordAliases,
            'category_keyword_aliases' => $context->keywordAliases,
            'user_category_patterns' => $categoryPatterns,
            'user_wallet_patterns' => $walletPatterns,
            'response_format' => '{"transactions":[{"transaction_type":null,"category_keyword":null,"source_wallet_keyword":null,"destination_wallet_keyword":null,"memory_candidates":[]}]}',
        ];

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function loadTemplate(string $name): string
    {
        $path = __DIR__.'/Templates/'.$name.'.prompt.md';

        return file_get_contents($path);
    }
}
