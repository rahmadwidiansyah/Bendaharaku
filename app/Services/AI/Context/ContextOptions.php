<?php

declare(strict_types=1);

namespace App\Services\AI\Context;

readonly class ContextOptions
{
    public function __construct(
        public bool $includeWalletBalance = false,
    ) {}
}
