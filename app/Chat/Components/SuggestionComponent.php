<?php

declare(strict_types=1);

namespace App\Chat\Components;

/**
 * Saran tindakan untuk user setelah error atau warning.
 *
 * Contoh: "Tambah dompet 'spay' di Web Dashboard."
 *
 * @param string      $messageKey  Translation key dari lang/x/chat.php
 * @param array       $params      Substitusi: ['name' => 'spay']
 * @param string|null $actionUrl   URL opsional untuk deep-link ke halaman relevan
 */
readonly class SuggestionComponent implements ChatComponentInterface
{
    public function __construct(
        public string  $messageKey,
        public array   $params    = [],
        public ?string $actionUrl = null,
    ) {}

    public function type(): string
    {
        return 'suggestion';
    }

    public function toArray(): array
    {
        return [
            'type'        => $this->type(),
            'message_key' => $this->messageKey,
            'params'      => $this->params,
            'action_url'  => $this->actionUrl,
        ];
    }
}
