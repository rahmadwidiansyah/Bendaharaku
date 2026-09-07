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
     *
     * SPEC §2-3: OCR pure extraction — only amounts with explicit monetary semantic label
     * are valid. Bare numbers (years, transaction IDs, reference numbers, timestamps)
     * MUST NOT be treated as amounts.
     *
     * Removed fallback patterns:
     * - '/\b(\d{1,3}(?:[.,]\d{3})+(?:[.,]\d{2})?)\b/' would capture years (2026), IDs (2026082443508344935851979)
     * - '/\b(\d{4,})\b/' would capture reference (168776804212), time fragments, account numbers
     *
     * Only patterns with explicit "Rp"/"IDR" or label (nominal/jumlah/total/amount/value/nilai) are allowed.
     */
    private const PATTERNS = [
        // "Nominal: Rp 25.000" atau "Jumlah: Rp 1.250.000" / "Jumlah Setor Tunai Rp 100.000"
        '/(?:nominal|jumlah|total|amount|value|nilai)[^\n]{0,40}?rp\.?\s*([\d.,]+)/i',
        // "Rp 25.000" atau "Rp25.000" — requires Rp/IDR prefix (explicit monetary label)
        '/rp\.?\s*([\d.,]+)/i',
        // "IDR 50000" atau "IDR 50.000"
        '/idr\.?\s*([\d.,]+)/i',
    ];

    /**
     * Ekstrak amount dari text.
     * SPEC §3, §8 rule 12: Promotional amounts ("Cashback up to Rp100.000") MUST NOT become amount
     * unless another explicit transaction amount exists. We skip Rp amounts that are in promotional context.
     *
     * @return array{amount: float|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        foreach (self::PATTERNS as $pattern) {
            // Use preg_match_all to allow skipping promotional matches
            if (preg_match_all($pattern, $text, $allMatches, PREG_OFFSET_CAPTURE)) {
                foreach ($allMatches[1] as $idx => $match) {
                    $raw = $match[0];
                    $offset = $match[1];

                    // SPEC §8 rule 12: Skip promotional context — only if Rp is on same line as promotional keywords
                    // Find the line containing this Rp match
                    $lineStart = strrpos(substr($text, 0, $offset), "\n");
                    $lineStart = $lineStart === false ? 0 : $lineStart + 1;
                    $lineEnd = strpos($text, "\n", $offset);
                    $lineEnd = $lineEnd === false ? strlen($text) : $lineEnd;
                    $line = mb_substr($text, $lineStart, $lineEnd - $lineStart);
                    $lowerLine = mb_strtolower($line);
                    if (preg_match('/\b(cashback|promo|hadiah|bonus|up\s*to|hingga)\b/iu', $lowerLine)) {
                        // This Rp is promotional on same line (e.g., "Cashback up to Rp100.000") — skip
                        // Total Pembayaran on separate line will not be skipped
                        continue;
                    }

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
