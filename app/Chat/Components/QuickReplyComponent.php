<?php

declare(strict_types=1);

namespace App\Chat\Components;

/**
 * Tombol pilihan cepat untuk user (platform yang support).
 *
 * Telegram  : Reply Keyboard / Inline Keyboard
 * Web       : Button chips
 * WhatsApp  : Interactive List (jika tersedia)
 * Discord   : Component Buttons
 * Platform tanpa support (SMS, email): lewati saja.
 *
 * Setiap item adalah ['label' => '...', 'value' => '...']
 * $value dikirim kembali sebagai pesan jika user memilih.
 */
readonly class QuickReplyComponent implements ChatComponentInterface
{
    /**
     * @param  array<int, array{label: string, value: string}>  $options
     */
    public function __construct(
        public array $options,
    ) {}

    public function type(): string
    {
        return 'quick_reply';
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'options' => $this->options,
        ];
    }
}
