<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

/**
 * ReferenceExtractor — Ekstrak nomor referensi dari OCR text.
 *
 * Mendukung format:
 * - Reference Number: ABC123456
 * - Ref: ABC123456
 * - RRN: 123456789012
 * - No. Referensi: ABC123456
 */
class ReferenceExtractor
{
    private const PATTERNS = [
        '/(?:reference\s*(?:number)?|ref\.?\s*(?:number)?|rrn|no\.?\s*referensi|nomor\s*referensi)[:\s]*([A-Z0-9]{6,30})/i',
        '/\b([A-Z]{2,5}\d{6,20})\b/',
        '/\b(\d{12,16})\b/',
    ];

    /**
     * Ekstrak reference number dari text.
     *
     * @return array{reference_number: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $raw = trim($matches[1]);

                if (strlen($raw) >= 6) {
                    return [
                        'reference_number' => $raw,
                        'confidence' => 0.95,
                        'raw' => $raw,
                    ];
                }
            }
        }

        return [
            'reference_number' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }
}
