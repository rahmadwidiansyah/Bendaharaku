<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

class IssuerExtractor
{
    private array $issuerAliases;

    public function __construct(?array $issuerAliases = null)
    {
        $this->issuerAliases = $issuerAliases ?? config('qris_parser.issuer_aliases', []);
    }

    /**
     * @return array{issuer: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        $lower = strtolower($text);

        foreach ($this->issuerAliases as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($lower, strtolower($alias))) {
                    return [
                        'issuer' => $canonical,
                        'confidence' => 0.9,
                        'raw' => $alias,
                    ];
                }
            }
        }

        // Label-based: cari "Issuer" atau "Penerbit" diikuti nama
        if (preg_match('/(?:issuer|penerbit)[:\s]*\n*([A-Za-z0-9\s]+)/i', $text, $m)) {
            $name = trim($m[1]);
            if (strlen($name) >= 3) {
                return [
                    'issuer' => $name,
                    'confidence' => 0.7,
                    'raw' => $name,
                ];
            }
        }

        return [
            'issuer' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }
}
