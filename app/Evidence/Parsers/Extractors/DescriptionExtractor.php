<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

/**
 * DescriptionExtractor — Ekstrak catatan/keterangan dari OCR text.
 *
 * Mendukung format:
 * - Keterangan: Top Up
 * - Catatan: Belanja Bulanan
 * - Description: Transfer
 */
class DescriptionExtractor
{
    private const PATTERNS = [
        '/(?:keterangan|catatan|description|note|deskripsi|memo)[:\s]*(.{3,100})/i',
        '/(?:berhasil|success|berhasil\s*(?:ditransfer|dikirim))[:\s]*(.{0,100})/i',
    ];

    /**
     * Ekstrak deskripsi/catatan dari text.
     *
     * @return array{description: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $raw = trim($matches[1]);

                if (strlen($raw) >= 2) {
                    return [
                        'description' => $raw,
                        'confidence' => 0.70,
                        'raw' => $raw,
                    ];
                }
            }
        }

        return [
            'description' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }
}
