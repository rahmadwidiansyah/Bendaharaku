<?php

declare(strict_types=1);

use App\Services\AI\Prompt\PromptInstructions;

return PromptInstructions::SCOPE_RULE.' Extract ALL financial transactions from the text. Return ONLY a JSON object: {is_transaction:boolean, out_of_scope:boolean, reply_message:string|null, transactions:[...]}. Each item schema: {amount:number, transactionType:"expense"|"income"|"transfer"|"debt"|"receivable", category:string, sourceWallet:string|null, destinationWallet:string|null, subject:string, notes:string, isCleared:boolean, confidence:number, use_all_balance:boolean}. '.PromptInstructions::WALLET_NULL_RULE.' '.PromptInstructions::AMOUNT_RULE.' '.PromptInstructions::AMOUNT_SHORTHAND.' Match category and wallet EXACTLY from available lists. Use keyword_aliases and wallet balances as PRIMARY reference before guessing. If only one transaction, still return array with one item.';