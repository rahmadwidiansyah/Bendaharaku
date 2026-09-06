<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

/**
 * SummaryExtractor — Ekstrak nilai dari baris summary struk belanja.
 *
 * Mendukung:
 * - Same-line: "TOTAL 23.000"
 * - Next-line: "TOTAL\n23.000"
 */
class SummaryExtractor
{
    private array $summaryLinePatterns;

    private array $summaryKeywords;

    public function __construct(
        ?array $summaryLinePatterns = null,
        ?array $summaryKeywords = null,
    ) {
        $this->summaryLinePatterns = $summaryLinePatterns ?? config('shopping_parser.summary_line_patterns', []);
        $this->summaryKeywords = $summaryKeywords ?? config('shopping_parser.summary_keywords', []);
    }

    /**
     * Ekstrak summary values dari text.
     *
     * @return array{total: float|null, subtotal: float|null, tax: float|null, discount: float|null, service_charge: float|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        $lines = array_map('trim', explode("\n", $text));
        $lines = array_values(array_filter($lines));

        $total = null;
        $subtotal = null;
        $tax = null;
        $discount = null;
        $serviceCharge = null;
        $foundAny = false;

        $lineCount = count($lines);

        for ($i = 0; $i < $lineCount; $i++) {
            $line = $lines[$i];
            $nextLine = ($i + 1 < $lineCount) ? $lines[$i + 1] : null;

            // Grand Total
            if (preg_match('/^(?:grand\s*)?total/i', $line) && $total === null) {
                $amount = $this->extractAmountFromLine($line, $nextLine);
                if ($amount !== null) {
                    $total = $amount;
                    $foundAny = true;
                }
            }

            // Subtotal
            if (preg_match('/^sub\s*total/i', $line) && $subtotal === null) {
                $amount = $this->extractAmountFromLine($line, $nextLine);
                if ($amount !== null) {
                    $subtotal = $amount;
                    $foundAny = true;
                }
            }

            // PPN / Pajak
            if (preg_match('/^(?:ppn|pajak|tax|pph)/i', $line) && $tax === null) {
                $amount = $this->extractAmountFromLine($line, $nextLine);
                if ($amount !== null) {
                    $tax = $amount;
                    $foundAny = true;
                }
            }

            // Diskon
            if (preg_match('/^(?:diskon|discount|potongan)/i', $line) && $discount === null) {
                $amount = $this->extractAmountFromLine($line, $nextLine);
                if ($amount !== null) {
                    $discount = $amount;
                    $foundAny = true;
                }
            }

            // Service Charge
            if (preg_match('/^service\s*charge/i', $line) && $serviceCharge === null) {
                $amount = $this->extractAmountFromLine($line, $nextLine);
                if ($amount !== null) {
                    $serviceCharge = $amount;
                    $foundAny = true;
                }
            }
        }

        // Jika total tidak ditemukan dari baris "Total", coba dari amount patterns
        if ($total === null) {
            $totalResult = $this->extractTotalFallback($text);
            if ($totalResult !== null) {
                $total = $totalResult;
                $foundAny = true;
            }
        }

        return [
            'total' => $total,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'service_charge' => $serviceCharge,
            'confidence' => $foundAny ? 0.9 : 0.0,
            'raw' => null,
        ];
    }

    /**
     * Ekstrak jumlah uang dari satu baris, dengan fallback ke baris berikutnya.
     *
     * Same-line: "TOTAL 23.000"
     * Next-line: "TOTAL" + "23.000"
     */
    private function extractAmountFromLine(string $line, ?string $nextLine = null): ?float
    {
        // Same-line: amount ada di baris yang sama
        if (preg_match('/(?:rp\.?\s*)?([\d.,]+)$/i', $line, $m)) {
            $amount = $this->parseNumber($m[1]);
            if ($amount > 0) {
                return $amount;
            }
        }

        // Next-line: baris berikutnya hanya berisi angka
        if ($nextLine !== null && preg_match('/^(?:rp\.?\s*)?([\d.,]+)$/i', $nextLine, $m)) {
            $amount = $this->parseNumber($m[1]);
            if ($amount > 0) {
                return $amount;
            }
        }

        return null;
    }

    /**
     * Fallback: ekstrak total dari text menggunakan patterns.
     * Prioritaskan Total Pembayaran/Total di footer, bukan Subtotal di tengah.
     */
    private function extractTotalFallback(string $text): ?float
    {
        $patterns = [
            // Total Pembayaran paling spesifik (Shopee): "Total Pembayaran Rp29.588" di baris akhir
            '/(?:total\s*pembayaran|grand\s*total|total\s*belanja|total)[:\s]*(?:rp\.?\s*)?([\d.,]+)/i',
            '/jumlah[:\s]*(?:rp\.?\s*)?([\d.,]+)/i',
        ];

        $candidates = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $m) {
                    $raw = $m[0];
                    $offset = $m[1];
                    $amount = $this->parseNumber($raw);
                    if ($amount > 0) {
                        $candidates[] = ['amount' => $amount, 'offset' => $offset];
                    }
                }
            }
        }
        if (! empty($candidates)) {
            // Pilih yang offset terbesar (paling bawah, Total final)
            usort($candidates, fn ($a, $b) => $b['offset'] <=> $a['offset']);

            return $candidates[0]['amount'];
        }

        return null;
    }

    /**
     * Parse angka dari string.
     */
    private function parseNumber(string $raw): float
    {
        return NumberParser::parse($raw);
    }
}
