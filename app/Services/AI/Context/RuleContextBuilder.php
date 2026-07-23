<?php

declare(strict_types=1);

namespace App\Services\AI\Context;

class RuleContextBuilder
{
    public function build(ContextSnapshot $snapshot): RuleContext
    {
        return new RuleContext(
            wallets: $snapshot->wallets,
            categories: $snapshot->categories,
            activeMemories: $snapshot->activeMemories,
        );
    }
}
