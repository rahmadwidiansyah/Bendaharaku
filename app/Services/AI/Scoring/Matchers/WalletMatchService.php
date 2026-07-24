<?php

declare(strict_types=1);

namespace App\Services\AI\Scoring\Matchers;

use App\Support\StringUtils;
use Illuminate\Database\Eloquent\Collection;

class WalletMatchService
{
    public function isMatch(Collection $wallets, ?string $walletText): bool
    {
        if (blank($walletText)) {
            return false;
        }

        return StringUtils::findByNameOrKeyword($wallets, $walletText) !== null;
    }
}
