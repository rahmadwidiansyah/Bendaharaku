<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

/**
 * AccountExtractor — Ekstrak nomor rekening/akun dari OCR text.
 *
 * Mendukung format:
 * - 1234567890
 * - 081234567890
 * - Rekening: 1234567890
 * - 901234567890
 */
class AccountExtractor
{
    private const PATTERNS = [
        '/(?:rekening|account|akun|nomor\s*(?:rekening|akun)|no\.?\s*(?:rek|acc))[:\s]*(\d{8,20})/i',
        '/(?:ke\s*(?:rekening|account)?|tujuan|penerima|recipient)[:\s]*(\d{8,20})/i',
        '/\b(\d{10,20})\b/',
    ];

    /**
     * Ekstrak nomor rekening dari text.
     *
     * @return array{account: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $raw = trim($matches[1]);

                if (strlen($raw) >= 8) {
                    return [
                        'account' => $raw,
                        'confidence' => 0.80,
                        'raw' => $raw,
                    ];
                }
            }
        }

        return [
            'account' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }
}
