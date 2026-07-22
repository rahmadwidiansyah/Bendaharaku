<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

class StatusExtractor
{
    private array $statusKeywords;

    public function __construct(?array $statusKeywords = null)
    {
        $this->statusKeywords = $statusKeywords ?? config('qris_parser.status_keywords', []);
    }

    /**
     * @return array{transaction_status: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        $lower = strtolower($text);

        foreach ($this->statusKeywords as $status => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, strtolower($keyword))) {
                    return [
                        'transaction_status' => $status,
                        'confidence' => 0.95,
                        'raw' => $keyword,
                    ];
                }
            }
        }

        // Label-based
        if (preg_match('/(?:status)[:\s]*\n*([A-Za-z\s]+)/i', $text, $m)) {
            $status = trim(strtoupper($m[1]));
            if (in_array($status, ['BERHASIL', 'SUCCESS', 'GAGAL', 'FAILED', 'PENDING'])) {
                return [
                    'transaction_status' => $status === 'SUCCESS' ? 'BERHASIL' : $status,
                    'confidence' => 0.8,
                    'raw' => $m[1],
                ];
            }
        }

        return [
            'transaction_status' => 'BERHASIL',
            'confidence' => 0.5,
            'raw' => null,
        ];
    }
}
