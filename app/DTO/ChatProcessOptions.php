<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Opsi pemrosesan chat lintas caller.
 *
 * Evidence grouping butuh multi-transaksi yang ketat: jika pool AI multi habis,
 * jangan diam-diam jatuh ke single karena makna transaksi bisa berubah.
 */
final readonly class ChatProcessOptions
{
    public function __construct(
        public bool $allowSingleFallback = true,
    ) {}
}
