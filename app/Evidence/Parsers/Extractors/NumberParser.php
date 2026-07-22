<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

/**
 * NumberParser — Parse angka dari string OCR dengan format Indonesia.
 *
 * Mendukung:
 * - "23.000" → 23000.0 (titik sebagai ribuan)
 * - "23.000,50" → 23000.50 (koma sebagai desimal)
 * - "23,000" → 23000.0 (koma sebagai ribuan)
 * - "23,000.50" → 23000.50 (titik sebagai desimal)
 */
class NumberParser
{
    /**
     * Parse string angka ke float.
     */
    public static function parse(string $raw): float
    {
        $raw = str_replace(' ', '', $raw);

        $lastComma = strrpos($raw, ',');
        $lastDot = strrpos($raw, '.');

        if ($lastComma !== false && $lastDot !== false) {
            // Keduanya ada — yang terakhir adalah desimal
            if ($lastComma > $lastDot) {
                // "1.250.000,50" → ribuan pakai titik, desimal pakai koma
                $raw = str_replace('.', '', substr($raw, 0, $lastComma)).substr($raw, $lastComma);
                $raw = str_replace(',', '.', $raw);
            } else {
                // "1,250,000.50" → ribuan pakai koma, desimal pakai titik
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($lastComma !== false) {
            // Hanya koma
            $parts = explode(',', $raw);
            if (count($parts) === 2 && strlen($parts[1]) <= 2) {
                // Desimal: "25,50"
                $raw = str_replace('.', '', $parts[0]).'.'.$parts[1];
            } else {
                // Ribuan: "25,000"
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($lastDot !== false) {
            // Hanya titik
            $parts = explode('.', $raw);
            $lastPart = end($parts);
            if (strlen($lastPart) === 3 && count($parts) > 1) {
                // Ribuan: "25.000"
                $raw = str_replace('.', '', $raw);
            }
            // Else: desimal "25.50" — biarkan saja
        }

        $result = filter_var($raw, FILTER_VALIDATE_FLOAT);

        return $result !== false ? $result : 0.0;
    }
}
