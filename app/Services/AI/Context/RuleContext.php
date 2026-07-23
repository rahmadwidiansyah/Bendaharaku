<?php

declare(strict_types=1);

namespace App\Services\AI\Context;

use Illuminate\Database\Eloquent\Collection;

readonly class RuleContext
{
    public function __construct(
        public Collection $wallets,
        public Collection $categories,
        public array $activeMemories,
    ) {}
}
