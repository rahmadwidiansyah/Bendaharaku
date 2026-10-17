<?php

namespace App\Support;

/**
 * M3ColorScheme — sumber kebenaran PHP untuk skema warna Material 3.
 *
 * Kunci di sini harus sinkron dengan:
 *  - resources/css/m3-schemes.css (blok data-m3-scheme per skema)
 *  - resources/js/Composables/useM3ColorScheme.js (peta label/deskripsi)
 *  - resources/js/Material/m3SchemeData.generated.js (warna pratinjau)
 *
 * Preferensi ini INDEPENDEN dari accent_color milik UI Default.
 */
class M3ColorScheme
{
    /** Skema yang tersedia (urutan = urutan tampil di pengaturan). */
    public const SCHEMES = ['teal', 'baseline', 'ocean', 'forest', 'sunset', 'rose', 'midnight'];

    public const DEFAULT = 'teal';

    public static function isValid(?string $value): bool
    {
        return $value !== null && in_array($value, self::SCHEMES, true);
    }

    public static function normalize(?string $value): string
    {
        return self::isValid($value) ? $value : self::DEFAULT;
    }
}
