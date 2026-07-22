<?php

declare(strict_types=1);

namespace App\Evidence\Parsers\Extractors;

/**
 * WalletExtractor — Ekstrak nama bank/wallet dari OCR text.
 *
 * Mendukung: SeaBank, BCA, BRI, Mandiri, BNI, ShopeePay, GoPay, OVO, DANA, LinkAja, dll.
 */
class WalletExtractor
{
    private const WALLETS = [
        'seabank' => 'SeaBank',
        'sea bank' => 'SeaBank',
        'bca' => 'BCA',
        'bank central asia' => 'BCA',
        'bri' => 'BRI',
        'bank rakyat indonesia' => 'BRI',
        'bni' => 'BNI',
        'bank negara indonesia' => 'BNI',
        'mandiri' => 'Mandiri',
        'bank mandiri' => 'Mandiri',
        'shopeepay' => 'ShopeePay',
        'shopee pay' => 'ShopeePay',
        'shopee' => 'ShopeePay',
        'gopay' => 'GoPay',
        'go pay' => 'GoPay',
        'ovo' => 'OVO',
        'dana' => 'DANA',
        'linkaja' => 'LinkAja',
        'link aja' => 'LinkAja',
        'jago' => 'Jago',
        'blu' => 'Blu',
        'neobank' => 'Neobank',
        'neo bank' => 'Neobank',
        'permata' => 'Permata',
        'danamon' => 'Danamon',
        'cimb' => 'CIMB',
        'bsi' => 'BSI',
        'bank syariah indonesia' => 'BSI',
        'maybank' => 'MayBank',
        'muamalat' => 'Muamalat',
    ];

    /**
     * Ekstrak nama bank/wallet dari text.
     *
     * @return array{wallet_name: string|null, confidence: float, raw: string|null}
     */
    public function extract(string $text): array
    {
        $lower = strtolower($text);

        foreach (self::WALLETS as $keyword => $walletName) {
            if (str_contains($lower, $keyword)) {
                return [
                    'wallet_name' => $walletName,
                    'bank_name' => $walletName,
                    'confidence' => 1.0,
                    'raw' => $keyword,
                ];
            }
        }

        return [
            'wallet_name' => null,
            'bank_name' => null,
            'confidence' => 0.0,
            'raw' => null,
        ];
    }
}
