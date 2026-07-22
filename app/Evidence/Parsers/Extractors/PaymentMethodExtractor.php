<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

/**
 * PaymentMethodExtractor — Ekstrak metode pembayaran dari OCR text struk belanja.
 *
 * Mendukung: Tunai, Debit, Kredit, QRIS, GoPay, OVO, DANA, ShopeePay, LinkAja, Transfer.
 */
class PaymentMethodExtractor
{
    private array $paymentMethods;

    public function __construct(?array $paymentMethods = null)
    {
        $this->paymentMethods = $paymentMethods ?? config('shopping_parser.payment_methods', []);
    }

    /**
     * Ekstrak payment method dari text.
     *
     * @return array{payment_method: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        $lower = strtolower($text);

        foreach ($this->paymentMethods as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($lower, strtolower($alias))) {
                    return [
                        'payment_method' => $canonical,
                        'confidence' => 0.9,
                        'raw' => $alias,
                    ];
                }
            }
        }

        return [
            'payment_method' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }
}
