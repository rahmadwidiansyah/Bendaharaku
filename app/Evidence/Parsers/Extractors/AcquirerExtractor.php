<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

class AcquirerExtractor
{
    private array $acquirerAliases;

    public function __construct(?array $acquirerAliases = null)
    {
        $this->acquirerAliases = $acquirerAliases ?? config('qris_parser.acquirer_aliases', []);
    }

    /**
     * @return array{acquirer: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        $lower = strtolower($text);

        foreach ($this->acquirerAliases as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($lower, strtolower($alias))) {
                    return [
                        'acquirer' => $canonical,
                        'confidence' => 0.9,
                        'raw' => $alias,
                    ];
                }
            }
        }

        if (preg_match('/(?:acquirer|akuisitor)[:\s]*\n*([A-Za-z0-9\s]+)/i', $text, $m)) {
            $name = trim($m[1]);
            if (strlen($name) >= 3) {
                return [
                    'acquirer' => $name,
                    'confidence' => 0.7,
                    'raw' => $name,
                ];
            }
        }

        return [
            'acquirer' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }
}
