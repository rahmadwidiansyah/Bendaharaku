<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

use App\Evidence\DTO\ReceiptItem;

/**
 * ItemExtractor — Ekstrak daftar item/baris dari OCR text struk belanja.
 *
 * Mendukung format:
 * - "2 x 4.000 8.000" (qty x harga total)
 * - "AQUA 600ML 8.000" (nama harga)
 * - "ROTI 15.000" (nama harga)
 */
class ItemExtractor
{
    private array $itemPatterns;

    private array $summaryKeywords;

    private array $skipKeywords;

    public function __construct(
        ?array $itemPatterns = null,
        ?array $summaryKeywords = null,
        ?array $skipKeywords = null,
    ) {
        $this->itemPatterns = $itemPatterns ?? config('shopping_parser.item_patterns', []);
        $this->summaryKeywords = $summaryKeywords ?? config('shopping_parser.summary_keywords', []);
        $this->skipKeywords = $skipKeywords ?? config('shopping_parser.skip_keywords', []);
    }

    /**
     * Ekstrak items dari text.
     *
     * @return array{items: ReceiptItem[], confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        $lines = array_map('trim', explode("\n", $text));
        $items = [];

        foreach ($lines as $line) {
            if (strlen($line) < 3) {
                continue;
            }

            // Skip summary lines
            if ($this->isSummaryLine($line)) {
                continue;
            }

            // Skip non-item lines
            if ($this->shouldSkipLine($line)) {
                continue;
            }

            $item = $this->parseItemLine($line);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        $confidence = count($items) > 0 ? 0.85 : 0.0;

        return [
            'items' => $items,
            'confidence' => $confidence,
            'raw' => $text,
        ];
    }

    /**
     * Parse satu baris menjadi ReceiptItem.
     */
    private function parseItemLine(string $line): ?ReceiptItem
    {
        // Pattern 1: "2 x 4.000 8.000" (qty x harga total)
        if (preg_match('/^(.+?)\s+(\d+)\s*x\s*(?:rp\.?\s*)?([\d.,]+)\s+(?:rp\.?\s*)?([\d.,]+)$/i', $line, $m)) {
            $name = trim($m[1]);
            $qty = (int) $m[2];
            $unitPrice = $this->parseNumber($m[3]);
            $total = $this->parseNumber($m[4]);

            if ($unitPrice > 0 && $total > 0 && $name !== '') {
                return new ReceiptItem(
                    name: $name,
                    qty: $qty,
                    unitPrice: $unitPrice,
                    total: $total,
                    confidence: 0.9,
                );
            }
        }

        // Pattern 2: "AQUA 600ML 8.000" (nama harga)
        if (preg_match('/^(.+?)\s+(?:rp\.?\s*)?([\d.,]+)$/i', $line, $m)) {
            $name = trim($m[1]);
            $total = $this->parseNumber($m[2]);

            // Pastikan nama bukan angka atau kata kunci summary
            if ($total > 0 && $name !== '' && ! is_numeric($name) && ! $this->isSummaryKeyword($name)) {
                return new ReceiptItem(
                    name: $name,
                    qty: 1,
                    unitPrice: $total,
                    total: $total,
                    confidence: 0.75,
                );
            }
        }

        // Pattern 3: "2 x 4.000" (qty x harga, tanpa total)
        if (preg_match('/^(.+?)\s+(\d+)\s*x\s*(?:rp\.?\s*)?([\d.,]+)$/i', $line, $m)) {
            $name = trim($m[1]);
            $qty = (int) $m[2];
            $unitPrice = $this->parseNumber($m[3]);

            if ($unitPrice > 0 && $name !== '' && ! $this->isSummaryKeyword($name)) {
                return new ReceiptItem(
                    name: $name,
                    qty: $qty,
                    unitPrice: $unitPrice,
                    total: $qty * $unitPrice,
                    confidence: 0.7,
                );
            }
        }

        return null;
    }

    /**
     * Cek apakah baris adalah summary line.
     */
    private function isSummaryLine(string $line): bool
    {
        $lower = strtolower($line);

        foreach ($this->summaryKeywords as $keyword) {
            if (str_contains($lower, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek apakah baris harus di-skip.
     */
    private function shouldSkipLine(string $line): bool
    {
        $lower = strtolower(trim($line));

        foreach ($this->skipKeywords as $keyword) {
            if (str_contains($lower, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek apakah string adalah summary keyword.
     */
    private function isSummaryKeyword(string $value): bool
    {
        $lower = strtolower(trim($value));

        foreach ($this->summaryKeywords as $keyword) {
            if (strtolower($keyword) === $lower) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse angka dari string.
     */
    private function parseNumber(string $raw): float
    {
        return NumberParser::parse($raw);
    }
}
