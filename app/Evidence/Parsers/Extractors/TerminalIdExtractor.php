<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

class TerminalIdExtractor
{
    /**
     * @return array{terminal_id: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        $patterns = config('qris_parser.terminal_label_patterns', [
            '/nmid[:\s]*([A-Za-z0-9]+)/i',
            '/terminal[:\s]*(?:id|no)?[:\s]*([A-Za-z0-9]+)/i',
            '/mid[:\s]*([A-Za-z0-9]+)/i',
        ]);

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $value = trim($matches[1]);
                if (strlen($value) >= 4) {
                    return [
                        'terminal_id' => $value,
                        'confidence' => 0.9,
                        'raw' => $value,
                    ];
                }
            }
        }

        return [
            'terminal_id' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }
}
