<?php

declare(strict_types=1);

namespace App\Services\AI\Prompt;

class TransactionPromptBuilder
{
    /**
     * Membangun format teks perintah seragam untuk LLM berbasis BYOK.
     */
    public function build(string $text, array $wallets, array $categories): string
    {
        $walletNames = array_map(fn($w) => $w['name'] ?? '', $wallets);
        $categoryNames = array_map(fn($c) => $c['category_name'] ?? '', $categories);

        return json_encode([
            'instruction' => 'Extract financial transaction. Return strictly JSON matching this schema: {amount: number, transactionType: "expense"|"income"|"transfer"|"debt"|"receivable", category: string, sourceWallet: string, destinationWallet: string, subject: string, notes: string, isCleared: boolean, confidence: number}. The "confidence" field must be a float between 0.0 and 1.0 representing your certainty of the extraction.',
            'text' => $text,
            'available_wallets' => array_values(array_filter($walletNames)),
            'available_categories' => array_values(array_filter($categoryNames)),
        ], JSON_THROW_ON_ERROR);
    }
}