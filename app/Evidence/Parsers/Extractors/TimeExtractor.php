<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

class TimeExtractor
{
    private array $patterns;

    public function __construct(?array $patterns = null)
    {
        $this->patterns = $patterns ?? config('qris_parser.time_patterns', [
            '/(\d{1,2})[.:](\d{2})(?:\s*(?:WIB|WITA|WIT))?/',
        ]);
    }

    /**
     * @return array{time: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        foreach ($this->patterns as $pattern) {
            if (preg_match_all($pattern, $text, $allMatches, PREG_SET_ORDER)) {
                foreach ($allMatches as $matches) {
                    $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $minute = str_pad($matches[2], 2, '0', STR_PAD_LEFT);

                    if ((int) $hour >= 0 && (int) $hour <= 23 && (int) $minute >= 0 && (int) $minute <= 59) {
                        $time = "{$hour}:{$minute}";
                        return [
                            'time' => $time,
                            'confidence' => 0.9,
                            'raw' => $matches[0],
                        ];
                    }
                }
            }
        }

        return [
            'time' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }
}
