<?php

declare(strict_types=1);

namespace App\Services\AI\Prompt;

/**
 * Membangun prompt untuk LLM agar mengembalikan ARRAY transaksi.
 *
 * Berbeda dengan TransactionPromptBuilder (single transaction),
 * builder ini meminta JSON schema { "transactions": [...] }.
 */
class MultiTransactionPromptBuilder
{
    public function build(string $text, array $wallets, array $categories, array $activeMemories = []): string
    {
        $walletNames   = array_values(array_filter(array_map(fn($w) => $w['name'] ?? '', $wallets)));
        $categoryNames = array_values(array_filter(array_map(fn($c) => $c['category_name'] ?? '', $categories)));

        $historicalMappings = array_map(fn($m) => [
            'keyword'         => $m['keyword'],
            'target_category' => $m['category'],
        ], $activeMemories);

        return json_encode([
            'instruction' => 'Extract ALL financial transactions from the text. Return ONLY a JSON object: {"transactions":[...]}. Each item schema: {amount:number, transactionType:"expense"|"income"|"transfer"|"debt"|"receivable", category:string, sourceWallet:string, destinationWallet:string, subject:string, notes:string, isCleared:boolean, confidence:number}. If only one transaction, still return array with one item. Amount shorthand: 20k=20000, 50rb=50000, 2jt=2000000, 1.5jt=1500000. Match category and wallet exactly from available lists.',
            'text'                    => $text,
            'available_wallets'       => $walletNames,
            'available_categories'    => $categoryNames,
            'historical_patterns'     => $historicalMappings,
            'response_format'         => '{"transactions":[{...},{...}]}',
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
