<?php

declare(strict_types=1);

namespace App\Services\AI\Prompt;

/**
 * MultiTransactionPromptBuilder
 *
 * Membangun prompt untuk LLM agar mengembalikan ARRAY transaksi.
 * Menyertakan context lengkap user: wallet, kategori, keyword alias, merchant.
 *
 * PENTING: LLM diarahkan untuk TIDAK menebak wallet jika tidak disebutkan.
 * Jika wallet tidak disebut → sourceWallet = null → sistem membuat draft.
 */
class MultiTransactionPromptBuilder
{
    public function build(
        string $text,
        array  $wallets,
        array  $categories,
        array  $activeMemories = [],
        array  $context        = []
    ): string {
        $walletNames   = array_values(array_filter(array_map(fn ($w) => $w['name'] ?? '', $wallets)));
        $categoryNames = array_values(array_filter(array_map(fn ($c) => $c['category_name'] ?? '', $categories)));

        $historicalMappings = array_map(fn ($m) => [
            'keyword'         => $m['keyword'],
            'target_category' => $m['category'],
        ], $activeMemories);

        // Keyword alias dari UserContextBuilder (jika tersedia)
        $walletKeywords   = $context['wallet_keywords']   ?? [];
        $categoryKeywords = $context['category_keywords'] ?? [];
        $recentMerchants  = $context['recent_merchants']  ?? [];

        $payload = [
            'instruction' => implode(' ', [
                'Extract ALL financial transactions from the text.',
                'Return ONLY a JSON object: {"transactions":[...]}.',
                'Each item schema: {amount:number, transactionType:"expense"|"income"|"transfer"|"debt"|"receivable",',
                'category:string, sourceWallet:string|null, destinationWallet:string|null,',
                'subject:string, notes:string, isCleared:boolean, confidence:number}.',
                'CRITICAL RULE: if the wallet/dompet is NOT explicitly mentioned by the user,',
                'set sourceWallet=null and isCleared=false \u2014 do NOT default to cash or any wallet.',
                'Amount shorthand: 20k=20000, 50rb=50000, 2jt=2000000, 1.5jt=1500000.',
                'Match category and wallet EXACTLY from available lists.',
                'Use keyword_aliases as the PRIMARY reference before guessing.',
                'If only one transaction, still return array with one item.',
            ]),
            'text'                     => $text,
            'available_wallets'        => $walletNames,
            'available_categories'     => $categoryNames,
            'wallet_keyword_aliases'   => $walletKeywords,
            'category_keyword_aliases' => $categoryKeywords,
            'historical_patterns'      => $historicalMappings,
            'response_format'          => '{"transactions":[{...},{...}]}',
        ];

        if (!empty($recentMerchants)) {
            $payload['known_merchants'] = $recentMerchants;
        }

        return json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
