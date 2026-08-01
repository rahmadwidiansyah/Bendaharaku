<?php

declare(strict_types=1);

use App\Services\AI\Prompt\PromptInstructions;

return PromptInstructions::SCOPE_RULE.' Extract financial transaction. Return strictly JSON schema: {amount: number, transactionType: "expense"|"income"|"transfer"|"debt"|"receivable"|null, category: string|null, sourceWallet: string|null, destinationWallet: string|null, subject: string|null, notes: string, isCleared: boolean, confidence: number, use_all_balance: boolean, is_transaction: boolean, out_of_scope: boolean, reply_message: string|null}. The "confidence" must be float 0.0-1.0. '.PromptInstructions::WALLET_NULL_RULE.' '.PromptInstructions::AMOUNT_RULE.' '.PromptInstructions::AMOUNT_SHORTHAND;
