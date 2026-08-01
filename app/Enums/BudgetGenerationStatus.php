<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status proses generate budget AI (async queue).
 *
 * Alur: pending → processing → completed
 *                          ↘ failed
 */
enum BudgetGenerationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return $this === self::Completed || $this === self::Failed;
    }
}
