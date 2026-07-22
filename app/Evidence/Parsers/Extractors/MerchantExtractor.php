<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

/**
 * MerchantExtractor — Ekstrak nama merchant/toko dari OCR text struk belanja.
 *
 * Mencocokkan nama merchant dari daftar alias yang dikonfigurasi.
 * Jika tidak cocok, mengambil baris pertama yang relevan.
 */
class MerchantExtractor
{
    private array $merchantAliases;

    public function __construct(?array $merchantAliases = null)
    {
        $this->merchantAliases = $merchantAliases ?? config('shopping_parser.merchant_aliases', []);
    }

    /**
     * Ekstrak merchant name dari text.
     *
     * @return array{merchant_name: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        $lines = array_map('trim', explode("\n", $text));

        // 1. Coba cocokkan dengan merchant aliases
        foreach ($lines as $line) {
            $lower = strtolower($line);

            foreach ($this->merchantAliases as $canonical => $aliases) {
                foreach ($aliases as $alias) {
                    if (str_contains($lower, strtolower($alias))) {
                        return [
                            'merchant_name' => $canonical,
                            'confidence' => 0.95,
                            'raw' => $line,
                        ];
                    }
                }
            }
        }

        // 2. Fallback: ambil baris pertama yang pendek dan masuk akal sebagai nama toko
        foreach ($lines as $line) {
            if (strlen($line) >= 3 && strlen($line) <= 50) {
                // Skip baris yang jelas bukan nama toko
                if ($this->isStoreNameCandidate($line)) {
                    return [
                        'merchant_name' => trim($line),
                        'confidence' => 0.6,
                        'raw' => $line,
                    ];
                }
            }
        }

        return [
            'merchant_name' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }

    /**
     * Cek apakah baris kandidat nama toko.
     */
    private function isStoreNameCandidate(string $line): bool
    {
        $skipPatterns = [
            '/^\d+$/',
            '/^(tanggal|date|time|waktu|kasir|cashier|no|nomor)/i',
            '/^(total|subtotal|ppn|pajak|diskon|discount)/i',
            '/^(terima kasih|thank you|selamat)/i',
        ];

        foreach ($skipPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return false;
            }
        }

        return true;
    }
}
