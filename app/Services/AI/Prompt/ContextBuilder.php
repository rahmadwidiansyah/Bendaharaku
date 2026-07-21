<?php

declare(strict_types=1);

namespace App\Services\AI\Prompt;

class ContextBuilder
{
    /**
     * Build the minimum required context for the LLM based on the input text.
     */
    public function build(string $text, array $wallets, array $categories, array $activeMemories): array
    {
        $lowerText = mb_strtolower($text);

        // 1. Detect if it's a Transfer
        $isTransfer = str_contains($lowerText, 'transfer') ||
                      str_contains($lowerText, 'pindah') ||
                      str_contains($lowerText, 'kirim') ||
                      str_contains($lowerText, 'mutasi');

        // 2. Detect if it's Debt/Receivable
        $isDebtOrReceivable = str_contains($lowerText, 'hutang') ||
                              str_contains($lowerText, 'utang') ||
                              str_contains($lowerText, 'pinjam') ||
                              str_contains($lowerText, 'piutang') ||
                              str_contains($lowerText, 'lunas') ||
                              str_contains($lowerText, 'balikin') ||
                              str_contains($lowerText, 'kembali');

        // 3. Detect if it's a transaction at all (contains numbers or transaction keywords)
        $hasNumbers = preg_match('/\d/', $text) === 1;
        $isTransaction = $hasNumbers || $isTransfer || $isDebtOrReceivable ||
                         str_contains($lowerText, 'beli') ||
                         str_contains($lowerText, 'bayar') ||
                         str_contains($lowerText, 'makan') ||
                         str_contains($lowerText, 'gaji') ||
                         str_contains($lowerText, 'bonus');

        // Modular context construction
        $context = [];

        if (!$isTransaction) {
            // General query / Out of scope -> do not send wallets, categories, or historical patterns
            return $context;
        }

        // Add Wallets context (only names and keywords, NO balances unless requested)
        $walletList = [];
        foreach ($wallets as $w) {
            if (empty($w['name'])) continue;
            
            // We omit the actual 'balance' for parsing transactions to limit scope and protect privacy.
            $walletList[] = [
                'name' => $w['name'],
                'keyword' => $w['keyword'] ?? '',
            ];
        }
        $context['available_wallets'] = $walletList;

        if ($isTransfer) {
            // Transfer does not need categories (as it is always mapped to Transfer Saldo/Pindah Saldo)
            // nor does it need historical patterns (which are for expenses/incomes)
            return $context;
        }

        // Add Categories context
        $categoryList = [];
        foreach ($categories as $c) {
            if (empty($c['category_name'])) continue;
            $categoryList[] = [
                'name' => $c['category_name'],
                'keyword' => $c['keyword'] ?? '',
            ];
        }
        $context['available_categories'] = $categoryList;

        // Add historical patterns if relevant memories are found
        if (!empty($activeMemories)) {
            $historicalMappings = array_map(function ($m) {
                return ['keyword' => $m['keyword'], 'target_category' => $m['category']];
            }, $activeMemories);
            $context['user_historical_patterns'] = $historicalMappings;
        }

        return $context;
    }
}
