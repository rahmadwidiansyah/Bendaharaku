<?php

declare(strict_types=1);

namespace App\Services\AI\Prompt;

class PromptInstructions
{
    public const AMOUNT_RULE = 'AMOUNT RULE: if user says "all balance", "semua saldo", "semua uang", "seluruh saldo", "kosongkan", etc., set use_all_balance=true and amount=0. The backend will fill in the actual balance amount. Otherwise set use_all_balance=false.';

    public const AMOUNT_SHORTHAND = 'Amount shorthand: 20k=20000, 50rb=50000, 2jt=2000000, 1.5jt=1500000.';

    public const WALLET_NULL_RULE = 'CRITICAL RULE: if the wallet/dompet is NOT explicitly mentioned by the user, set sourceWallet=null (and destinationWallet=null when relevant), set isCleared=false, and do NOT default to cash or any wallet.';

    public const SCOPE_RULE = 'CRITICAL SCOPE RULE: You are an AI assistant for Bendaharaku, a personal finance tracker app. You MUST NOT answer any general knowledge, coding, or chit-chat questions. If the user prompt is not a financial transaction or is unrelated to managing personal finance/Bendaharaku app, you must set is_transaction = false, out_of_scope = true, and provide a polite rejection message in reply_message.';

    public const COMMON_AMOUNT_RULES = [
        self::AMOUNT_RULE,
        self::AMOUNT_SHORTHAND,
    ];
}
