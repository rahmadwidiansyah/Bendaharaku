<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

/**
 * AmountExtractor — Ekstrak nominal/transaksi dari OCR text.
 *
 * Mendukung format:
 * - Rp25.000
 * - Rp 1.250.000
 * - IDR 50000
 * - Rp25.000,00
 * - Nominal: Rp 100.000
 */
class AmountExtractor
{
    /**
     * Regex patterns untuk amount (dalam urutan prioritas).
     */
    private const PATTERNS = [
        // "Nominal: Rp 25.000" atau "Jumlah: Rp 1.250.000"
        '/(?:nominal|jumlah|total|amount|value|nilai)[:\s]*rp\.?\s*([\d.,]+)/i',
        // "Rp 25.000" atau "Rp25.000"
        '/rp\.?\s*([\d.,]+)/i',
        // "IDR 50000" atau "IDR 50.000"
        '/idr\.?\s*([\d.,]+)/i',
        // Standalone large numbers (>= 4 digits) as fallback
        '/\b(\d{1,3}(?:[.,]\d{3})+(?:[.,]\d{2})?)\b/',
    ];

    /**
     * Ekstrak amount dari text.
     *
     * @return array{amount: float|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $raw = $matches[1];
                $amount = $this->parseAmount($raw);

                if ($amount !== null && $amount > 0) {
                    return [
                        'amount' => $amount,
                        'confidence' => 1.0,
                        'raw' => $raw,
                    ];
                }
            }
        }

        return [
            'amount' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }

    /**
     * Parse string amount ke float.
     *
     * "25.000" → 25000.0
     * "1.250.000,50" → 1250000.50
     */
    private function parseAmount(string $raw): ?float
    {
        // Hapus spasi
        $raw = str_replace(' ', '', $raw);

        // Deteksi format: titik sebagai ribuan, koma sebagai desimal
        // atau koma sebagai ribuan, titik sebagai desimal
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
                // Desimal: "25.000,50"
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

        return $result !== false ? $result : null;
    }
}
