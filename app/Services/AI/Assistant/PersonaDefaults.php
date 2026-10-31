<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant;

/**
 * PersonaDefaults — persona default Bendaharaku (lapis B).
 *
 * Persona default: ramah & natural, seperti asisten pribadi (bukan chatbot
 * formal), bahasa Indonesia sederhana, singkat, emoji secukupnya, boleh
 * komentar ringan & saran keuangan sederhana, tidak menghakimi, tidak
 * mengarang data.
 *
 * User overrides hanya boleh memakai key yang ada di sini (whitelist) —
 * sanitasi di PersonaValidator.
 */
final class PersonaDefaults
{
    public const OPTIONS = [
        'tone' => ['ramah', 'profesional', 'santai', 'energik'],
        'formality' => ['santai', 'semi-formal', 'formal'],
        'response_length' => ['singkat', 'sedang', 'detail'],
        'emoji' => ['tanpa', 'sedikit', 'sering'],
    ];

    public const DEFAULT = [
        'tone' => 'ramah',
        'formality' => 'semi-formal',
        'response_length' => 'singkat',
        'emoji' => 'sedikit',
        'custom_instructions' => null,
    ];

    /**
     * Persona efektif: default + override tersimpan (key di luar whitelist
     * dibuang, nilai tidak valid jatuh ke default).
     */
    public static function merge(?array $stored): array
    {
        return PersonaValidator::sanitize($stored);
    }

    public static function get(): array
    {
        return self::DEFAULT;
    }
}
