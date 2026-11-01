<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant;

/**
 * PersonaValidator — sanitasi + validasi persona pengguna.
 *
 * Persona adalah input TIDAK tepercaya (diedit pengguna). Aturan:
 * - Hanya key whitelist (tone, formality, response_length, emoji,
 *   custom_instructions) yang dipertahankan; sisanya dibuang.
 * - Nilai enum di luar opsi jatuh ke default — bukan error.
 * - custom_instructions: string ≤500 karakter, karakter kontrol dibuang;
 *   ini instruksi GAYA dengan otoritas terendah (lihat PersonaBlock).
 */
final class PersonaValidator
{
    private const MAX_CUSTOM_LENGTH = 500;

    public static function sanitize(mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $persona = PersonaDefaults::DEFAULT;

        foreach (array_keys(PersonaDefaults::OPTIONS) as $key) {
            $value = $input[$key] ?? null;
            if (is_string($value) && in_array($value, PersonaDefaults::OPTIONS[$key], true)) {
                $persona[$key] = $value;
            }
        }

        $persona['custom_instructions'] = self::sanitizeCustom($input['custom_instructions'] ?? null);

        return $persona;
    }

    /**
     * Aturan validasi Laravel untuk controller (array opsional).
     * Sanitasi detail tetap lewat sanitize().
     */
    public static function rules(): array
    {
        // Sengaja TANPA in: pada enum — nilai tidak valid ditolak secara
        // halus oleh sanitize() (jatuh ke default), sesuai desain graceful
        // fallback; validasi di sini hanya struktural.
        return [
            'bot_persona' => ['sometimes', 'array'],
            'bot_persona.tone' => ['sometimes', 'string', 'max:32'],
            'bot_persona.formality' => ['sometimes', 'string', 'max:32'],
            'bot_persona.response_length' => ['sometimes', 'string', 'max:32'],
            'bot_persona.emoji' => ['sometimes', 'string', 'max:32'],
            'bot_persona.custom_instructions' => ['sometimes', 'nullable', 'string', 'max:'.self::MAX_CUSTOM_LENGTH],
            'reset_persona' => ['sometimes', 'boolean'],
        ];
    }

    private static function sanitizeCustom(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        // Buang karakter kontrol (kecuali newline), rapikan spasi.
        $clean = preg_replace('/[^\P{C}\n]/u', '', $value) ?? '';
        $clean = trim(preg_replace('/[ \t]+/', ' ', $clean) ?? '');

        if ($clean === '') {
            return null;
        }

        return mb_substr($clean, 0, self::MAX_CUSTOM_LENGTH);
    }
}
