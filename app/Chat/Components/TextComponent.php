<?php

declare(strict_types=1);

namespace App\Chat\Components;

/**
 * Komponen teks sederhana.
 *
 * translationKey: key dari lang/x/chat.php, misal 'chat.general.processing'
 * params:         parameter substitusi (:count, :name, dll)
 * bold:           hint ke Formatter bahwa teks ini penting
 * literal:        true = translationKey adalah TEKS FINAL (bukan key) —
 *                 Formatter tidak memanggil trans(). Dipakai untuk pesan
 *                 natural hasil AI (AssistantResponse).
 *
 * Formatter memanggil trans($translationKey, $params) sesuai locale
 * kecuali literal. TextComponent TIDAK menyimpan teks final selain mode
 * literal.
 */
readonly class TextComponent implements ChatComponentInterface
{
    public function __construct(
        public string $translationKey,
        public array $params = [],
        public bool $bold = false,
        public bool $literal = false,
    ) {}

    public function type(): string
    {
        return 'text';
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'translationKey' => $this->translationKey,
            'params' => $this->params,
            'bold' => $this->bold,
        ];
    }
}
