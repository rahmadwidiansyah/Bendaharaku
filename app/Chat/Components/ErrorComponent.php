<?php

declare(strict_types=1);

namespace App\Chat\Components;

use App\Enums\ChatErrorSeverity;

/**
 * Komponen error untuk satu item yang gagal.
 *
 * $messageKey  — translation key, mis. 'chat.wallet.not_found'
 * $params      — substitusi parameter: ['name' => 'spay']
 * $raw         — teks input asli user untuk item ini
 * $index       — urutan item dalam batch (null = single)
 * $severity    — level keparahan untuk styling di UI
 * $recoverable — apakah user bisa retry atau perlu intervensi
 */
readonly class ErrorComponent implements ChatComponentInterface
{
    public function __construct(
        public string            $messageKey,
        public array             $params      = [],
        public ?string           $raw         = null,
        public ?int              $index       = null,
        public ChatErrorSeverity $severity    = ChatErrorSeverity::Error,
        public bool              $recoverable = true,
    ) {}

    public function type(): string
    {
        return 'error';
    }

    public function toArray(): array
    {
        return [
            'type'        => $this->type(),
            'message_key' => $this->messageKey,
            'params'      => $this->params,
            'raw'         => $this->raw,
            'index'       => $this->index,
            'severity'    => $this->severity->value,
            'recoverable' => $this->recoverable,
        ];
    }
}
