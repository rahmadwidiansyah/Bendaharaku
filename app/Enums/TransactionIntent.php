<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionIntent: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';
    case Debt = 'debt';
    case Receivable = 'receivable';

    public function toTypeKey(): string
    {
        return match ($this) {
            self::Income => 'income',
            self::Expense => 'expense',
            self::Transfer => 'transfer',
            self::Debt => 'debt',
            self::Receivable => 'receivable',
        };
    }

    public static function typeKeyFromName(?string $name, string $default = 'other'): string
    {
        if ($name === null || $name === '') {
            return $default;
        }

        $intent = self::tryFrom(strtolower($name));

        return $intent?->toTypeKey() ?? $default;
    }
}
