<?php

declare(strict_types=1);

namespace App\Services\AI\Prompt;

class TransactionPromptBuilder
{
    public function __construct(
        private readonly ContextBuilder $contextBuilder = new ContextBuilder()
    ) {}

    public function build(string $text, array $wallets, array $categories, array $activeMemories = []): string
    {
        $context = $this->contextBuilder->build($text, $wallets, $categories, $activeMemories);

        $payload = [
            'instruction' => implode(' ', [
                'CRITICAL SCOPE RULE: You are an AI assistant for Bendaharaku, a personal finance tracker app.',
                'You MUST NOT answer any general knowledge, coding, or chit-chat questions.',
                'If the user prompt is not a financial transaction or is unrelated to managing personal finance/Bendaharaku app,',
                'you must set is_transaction = false, out_of_scope = true, and provide a polite rejection message in reply_message.',
                'Extract financial transaction. Return strictly JSON schema:',
                '{amount: number, transactionType: "expense"|"income"|"transfer"|"debt"|"receivable"|null,',
                'category: string|null, sourceWallet: string|null, destinationWallet: string|null,',
                'subject: string|null, notes: string, isCleared: boolean, confidence: number, use_all_balance: boolean,',
                'is_transaction: boolean, out_of_scope: boolean, reply_message: string|null}.',
                'The "confidence" must be float 0.0-1.0.',
                'CRITICAL RULE: if the user does not explicitly mention a wallet/dompet,',
                'set sourceWallet=null (and destinationWallet=null when relevant),',
                'set isCleared=false, and do not default to cash or any wallet.',
                'AMOUNT RULE: if user says "all balance", "semua saldo", "semua uang", "seluruh saldo",',
                '"kosongkan", etc., set use_all_balance=true and amount=0.',
                'The backend will fill in the actual balance amount.',
                'Otherwise set use_all_balance=false.',
                'Amount shorthand: 20k=20000, 50rb=50000, 2jt=2000000, 1.5jt=1500000.',
            ]),
            'text' => $text,
        ];

        // Merge dynamically generated context from ContextBuilder
        $payload = array_merge($payload, $context);

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
