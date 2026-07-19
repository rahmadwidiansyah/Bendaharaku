<?php
declare(strict_types=1);
namespace App\Services\AI\Prompt;

class TransactionPromptBuilder
{
    public function build(string $text, array $wallets, array $categories, array $activeMemories = []): string
    {
        $walletNames = array_map(fn($w) => $w['name'] ?? '', $wallets);
        $categoryNames = array_map(fn($c) => $c['category_name'] ?? '', $categories);
        
        // PENTING: Kirimkan keyword beserta kategori tujuannya (Mapping)
        $historicalMappings = array_map(function($m) {
            return ['keyword' => $m['keyword'], 'target_category' => $m['category']];
        }, $activeMemories);

        return json_encode([
            'instruction' => 'Extract financial transaction. Return strictly JSON schema: {amount: number, transactionType: "expense"|"income"|"transfer"|"debt"|"receivable", category: string, sourceWallet: string|null, destinationWallet: string|null, subject: string, notes: string, isCleared: boolean, confidence: number}. The "confidence" must be float 0.0-1.0. Critical rule: if the user does not explicitly mention a wallet/dompet, set sourceWallet=null (and destinationWallet=null when relevant), set isCleared=false, and do not default to cash or any wallet.',
            'text' => $text,
            'available_wallets' => array_values(array_filter($walletNames)),
            'available_categories' => array_values(array_filter($categoryNames)),
            'historical_patterns_guidance' => 'Use these mappings as strong hints if the keyword matches the context.',
            'user_historical_patterns' => $historicalMappings 
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
