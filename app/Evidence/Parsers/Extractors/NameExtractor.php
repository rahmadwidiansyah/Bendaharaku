<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

/**
 * NameExtractor — Ekstrak nama tujuan/penerima dari OCR text.
 *
 * Mendukung format:
 * - Transfer ke Rahmad Widiansyah
 * - Penerima: Rahmad Widiansyah
 * - Atas Nama: Rahmad Widiansyah
 * - Recipient: Rahmad Widiansyah
 */
class NameExtractor
{
    private const PATTERNS = [
        '/(?:transfer\s*ke|penerima|recipient|atas\s*nama|ke\s*(?:nama)?)[:\s]*([A-Z][a-zA-Z\s]{2,50})/i',
        '/(?:to)\s*[:\s]*([A-Z][a-zA-Z\s]{2,50})/i',
    ];

    /**
     * Ekstrak nama tujuan dari text.
     *
     * @return array{name: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $raw = trim($matches[1]);

                // Filter: pastikan bukan keyword bank/wallet
                $lower = strtolower($raw);
                $bankKeywords = ['bank', 'seabank', 'bca', 'bri', 'bni', 'mandiri', 'shopee', 'gopay', 'ovo', 'dana'];

                $isBankKeyword = false;
                foreach ($bankKeywords as $kw) {
                    if (str_contains($lower, $kw)) {
                        $isBankKeyword = true;
                        break;
                    }
                }

                if (! $isBankKeyword && strlen($raw) >= 3) {
                    return [
                        'name' => $raw,
                        'confidence' => 0.75,
                        'raw' => $raw,
                    ];
                }
            }
        }

        return [
            'name' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }
}
