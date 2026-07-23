<?php

declare(strict_types=1);

namespace App\Services\AI\Prompt;

/**
 * MultiTransactionPromptBuilder
 *
 * Membangun prompt untuk LLM agar mengembalikan ARRAY transaksi.
 * Menyertakan context lengkap user: wallet dengan saldo, kategori, keyword alias, merchant.
 *
 * PENTING: LLM diarahkan untuk TIDAK menebak wallet jika tidak disebutkan.
 * Jika wallet tidak disebut → sourceWallet = null → sistem membuat draft.
 */
class MultiTransactionPromptBuilder
{
    public function build(
        string $text,
        array $wallets,
        array $categories,
        array $activeMemories = [],
        array $context = []
    ): string {
        $categoryNames = array_values(array_filter(array_map(fn ($c) => $c['category_name'] ?? '', $categories)));

        $historicalMappings = array_map(fn ($m) => [
            'keyword' => $m['keyword'],
            'target_category' => $m['category'],
        ], $activeMemories);

        // Keyword alias dari UserContextBuilder (jika tersedia)
        $walletKeywords = $context['wallet_keywords'] ?? [];
        $categoryKeywords = $context['category_keywords'] ?? [];
        $recentMerchants = $context['recent_merchants'] ?? [];

        // Bangun daftar wallet dengan saldo untuk context AI
        $walletList = array_map(function ($w) {
            return [
                'name' => $w['name'] ?? '',
                'balance' => (float) ($w['balance'] ?? 0),
            ];
        }, $wallets);
        $walletList = array_values(array_filter($walletList, fn ($w) => ! empty($w['name'])));

        $instruction = require resource_path('prompts/transaction-multi.php');

        $payload = [
            'instruction' => $instruction,
            'text' => $text,
            'available_wallets' => $walletList,
            'available_categories' => $categoryNames,
            'wallet_keyword_aliases' => $walletKeywords,
            'category_keyword_aliases' => $categoryKeywords,
            'historical_patterns' => $historicalMappings,
            'response_format' => '{"transactions":[{...},{...}]}',
        ];

        if (! empty($recentMerchants)) {
            $payload['known_merchants'] = $recentMerchants;
        }

        return json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
