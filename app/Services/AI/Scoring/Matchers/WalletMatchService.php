<?php

declare(strict_types=1);

namespace App\Services\AI\Scoring\Matchers;

use App\Models\User;
use App\Support\StringUtils;

class WalletMatchService
{
    public function isMatch(User $user, ?string $walletText): bool
    {
        if (blank($walletText)) {
            return false;
        }

        $wallets = $user->wallets()->get(['name', 'keyword']);

        return StringUtils::findByNameOrKeyword($wallets, $walletText) !== null;
    }
}
