<?php

declare(strict_types=1);

namespace App\Services\AI\Scoring\Matchers;

use App\Models\User;

class WalletMatchService
{
    public function isMatch(User $user, ?string $walletText): bool
    {
        if (blank($walletText)) return false;

        $search = strtolower(trim($walletText));
        $wallets = $user->wallets()->get(['name', 'keyword']);

        return $wallets->contains(function ($w) use ($search) {
            if (strtolower($w->name) === $search) return true;
            
            if (blank($w->keyword)) return false;
            $tokens = preg_split('/[,|;]+/', strtolower($w->keyword), -1, PREG_SPLIT_NO_EMPTY);
            return in_array($search, array_map('trim', $tokens), true);
        });
    }
}