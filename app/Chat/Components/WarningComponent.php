<?php

declare(strict_types=1);

namespace App\Chat\Components;

/**
 * Peringatan non-fatal. Operasi tetap berhasil tapi ada catatan.
 *
 * Contoh: "Confidence AI rendah, transaksi disimpan sebagai draft."
 *
 * messageKey: translation key dari lang/x/chat.php
 * params:     substitusi parameter
 */
readonly class WarningComponent implements ChatComponentInterface
{
    public function __construct(
        public string $messageKey,
        public array $params = [],
    ) {}

    public function type(): string
    {
        return 'warning';
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'message_key' => $this->messageKey,
            'params' => $this->params,
        ];
    }
}
