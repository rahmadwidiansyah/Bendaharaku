<?php

declare(strict_types=1);

namespace App\Chat\Components;

/**
 * Pemisah visual antar blok konten.
 *
 * Formatter memutuskan cara render:
 * - Telegram  : "─────────────────────"
 * - Web       : <hr> atau border CSS
 * - Discord   : newline kosong
 * - Platform tanpa support: lewati saja
 */
readonly class DividerComponent implements ChatComponentInterface
{
    public function type(): string
    {
        return 'divider';
    }

    public function toArray(): array
    {
        return ['type' => $this->type()];
    }
}
