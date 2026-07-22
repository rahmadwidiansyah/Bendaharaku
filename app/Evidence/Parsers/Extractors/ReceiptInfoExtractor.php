<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

/**
 * ReceiptInfoExtractor — Ekstrak informasi umum struk belanja.
 *
 * Mengekstrak: nomor struk, kasir, tanggal, waktu.
 */
class ReceiptInfoExtractor
{
    private array $receiptNumberPatterns;

    private array $cashierPatterns;

    private array $datePatterns;

    private array $timePatterns;

    public function __construct(
        ?array $receiptNumberPatterns = null,
        ?array $cashierPatterns = null,
        ?array $datePatterns = null,
        ?array $timePatterns = null,
    ) {
        $this->receiptNumberPatterns = $receiptNumberPatterns ?? config('shopping_parser.receipt_number_patterns', []);
        $this->cashierPatterns = $cashierPatterns ?? config('shopping_parser.cashier_patterns', []);
        $this->datePatterns = $datePatterns ?? config('shopping_parser.date_patterns', []);
        $this->timePatterns = $timePatterns ?? config('shopping_parser.time_patterns', []);
    }

    /**
     * Ekstrak receipt info dari text.
     *
     * @return array{receipt_number: string|null, cashier: string|null, date: string|null, time: string|null, confidence: float}
     */
    public function extract(string $text): array
    {
        $receiptNumber = $this->extractReceiptNumber($text);
        $cashier = $this->extractCashier($text);
        $date = $this->extractDate($text);
        $time = $this->extractTime($text);

        $foundCount = count(array_filter([
            $receiptNumber['receipt_number'],
            $cashier['cashier'],
            $date['date'],
            $time['time'],
        ], fn ($v) => $v !== null));

        $confidence = $foundCount > 0 ? 0.7 + ($foundCount * 0.075) : 0.0;

        return [
            'receipt_number' => $receiptNumber['receipt_number'],
            'cashier' => $cashier['cashier'],
            'date' => $date['date'],
            'time' => $time['time'],
            'confidence' => min($confidence, 1.0),
        ];
    }

    private function extractReceiptNumber(string $text): array
    {
        foreach ($this->receiptNumberPatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return ['receipt_number' => trim($m[1]), 'confidence' => 0.8];
            }
        }

        return ['receipt_number' => null, 'confidence' => 0.0];
    }

    private function extractCashier(string $text): array
    {
        foreach ($this->cashierPatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $name = trim($m[1]);
                // Bersihkan angka dari nama kasir
                $name = preg_replace('/^\d+\s*/', '', $name);
                $name = trim($name);

                if ($name !== '' && strlen($name) >= 2) {
                    return ['cashier' => $name, 'confidence' => 0.75];
                }
            }
        }

        return ['cashier' => null, 'confidence' => 0.0];
    }

    private function extractDate(string $text): array
    {
        foreach ($this->datePatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return ['date' => trim($m[1]), 'confidence' => 0.85];
            }
        }

        return ['date' => null, 'confidence' => 0.0];
    }

    private function extractTime(string $text): array
    {
        foreach ($this->timePatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return ['time' => trim($m[1]), 'confidence' => 0.8];
            }
        }

        return ['time' => null, 'confidence' => 0.0];
    }
}
