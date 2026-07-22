<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

use Carbon\Carbon;

/**
 * DateExtractor — Ekstrak tanggal/waktu dari OCR text.
 *
 * Mendukung format:
 * - 22 Jul 2026 09:30
 * - 22/07/2026 09:30:00
 * - 2026-07-22T09:30:00
 * - 22 Juli 2026
 * - 22 Jul 2026
 */
class DateExtractor
{
    private const PATTERNS = [
        // ISO format: 2026-07-22T09:30:00 atau 2026-07-22 09:30:00
        '/(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(?::\d{2})?)/',
        // DD/MM/YYYY HH:MM
        '/(\d{1,2}\/\d{1,2}\/\d{4}\s+\d{1,2}:\d{2}(?::\d{2})?)/',
        // DD-MM-YYYY HH:MM
        '/(\d{1,2}-\d{1,2}-\d{4}\s+\d{1,2}:\d{2}(?::\d{2})?)/',
        // "22 Jul 2026 09:30" atau "22 Jul 2026"
        '/(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Sep|Okt|Nov|Des|Januari|Februari|Maret|April|Juni|Juli|Agustus|September|Oktober|November|Desember|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\w*\s+\d{4}(?:\s+\d{1,2}:\d{2}(?::\d{2})?)?)/i',
        // "22 Juli 2026"
        '/(\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4}(?:\s+\d{1,2}:\d{2}(?::\d{2})?)?)/i',
        // DD/MM/YYYY (standalone date without time)
        '/(\d{1,2})\/(\d{1,2})\/(\d{4})/',
        // YYYY-MM-DD (standalone date without time)
        '/(\d{4})-(\d{2})-(\d{2})/',
    ];

    private const MONTH_MAP = [
        'jan' => '01', 'januari' => '01',
        'feb' => '02', 'februari' => '02',
        'mar' => '03', 'maret' => '03',
        'apr' => '04', 'april' => '04',
        'mei' => '05', 'may' => '05',
        'jun' => '06', 'juni' => '06',
        'jul' => '07', 'juli' => '07',
        'agu' => '08', 'agustus' => '08',
        'sep' => '09', 'september' => '09',
        'okt' => '10', 'oktober' => '10',
        'nov' => '11', 'november' => '11',
        'des' => '12', 'desember' => '12',
        'dec' => '12', 'december' => '12',
    ];

    /**
     * Ekstrak tanggal/waktu dari text.
     *
     * @return array{transaction_time: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $raw = count($matches) > 2
                    ? trim($matches[1].'/'.$matches[2].'/'.$matches[3])
                    : trim($matches[1]);

                $normalized = $this->normalizeDate($raw);

                if ($normalized !== null) {
                    return [
                        'transaction_time' => $normalized,
                        'confidence' => 0.85,
                        'raw' => $raw,
                    ];
                }
            }
        }

        return [
            'transaction_time' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }

    /**
     * Normalize tanggal ke format ISO 8601.
     */
    private function normalizeDate(string $raw): ?string
    {
        // Coba Carbon parse
        try {
            $date = Carbon::parse($raw);

            if ($date->year < 2000 || $date->year > 2100) {
                return null;
            }

            return $date->format('Y-m-d\TH:i:s');
        } catch (\Throwable) {
            // Coba manual parse
        }

        // DD/MM/YYYY or DD-MM-YYYY
        if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $raw, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = $m[3];
            if (checkdate((int) $month, (int) $day, (int) $year)) {
                return "{$year}-{$month}-{$day}T00:00:00";
            }
        }

        // Manual: "22 Jul 2026 09:30"
        if (preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?/', $raw, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $monthKey = strtolower(substr($m[2], 0, 3));
            $month = self::MONTH_MAP[$monthKey] ?? null;
            $year = $m[3];
            $hour = str_pad($m[4] ?? '00', 2, '0', STR_PAD_LEFT);
            $minute = str_pad($m[5] ?? '00', 2, '0', STR_PAD_LEFT);
            $second = str_pad($m[6] ?? '00', 2, '0', STR_PAD_LEFT);

            if ($month) {
                return "{$year}-{$month}-{$day}T{$hour}:{$minute}:{$second}";
            }
        }

        return null;
    }
}
