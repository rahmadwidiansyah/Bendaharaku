<?php

declare(strict_types=1);

namespace App\Services\AI\Context;

class BalanceIntentDetector
{
    private const ALL_BALANCE_KEYWORDS = [
        'semua saldo',
        'seluruh saldo',
        'semua uang',
        'seluruh uang',
        'kosongkan',
        'habiskan',
        'transfer semua',
        'pindahkan semua',
        'all balance',
        'transfer seluruh',
        'pindahkan seluruh',
    ];

    public function needsBalance(string $text): bool
    {
        $lower = mb_strtolower($text);

        if ($this->hasAllBalanceKeyword($lower)) {
            return true;
        }

        return false;
    }

    private function hasAllBalanceKeyword(string $text): bool
    {
        foreach (self::ALL_BALANCE_KEYWORDS as $kw) {
            if (str_contains($text, $kw)) {
                return true;
            }
        }

        return false;
    }
}
