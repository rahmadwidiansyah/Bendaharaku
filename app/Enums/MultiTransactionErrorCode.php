<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Kode error standar untuk kegagalan item dalam multi-transaction.
 *
 * Dipakai oleh processMulti() di ChatTransactionOrchestrator agar
 * formatter (Telegram, Web, dll) bisa membuat pesan yang kontekstual,
 * dan debugging log bisa difilter berdasarkan jenis kegagalan.
 */
enum MultiTransactionErrorCode: string
{
    /** Nama dompet yang disebutkan user tidak ditemukan di database. */
    case WALLET_NOT_FOUND = 'WALLET_NOT_FOUND';

    /** Nama kategori yang disebutkan user tidak ditemukan di database. */
    case CATEGORY_NOT_FOUND = 'CATEGORY_NOT_FOUND';

    /** Nominal transaksi nol, negatif, atau tidak terdeteksi AI. */
    case INVALID_AMOUNT = 'INVALID_AMOUNT';

    /** Source wallet dan destination wallet adalah entitas yang sama. */
    case SAME_WALLET = 'SAME_WALLET';

    /** Saldo tidak mencukupi dan user tidak mengizinkan saldo negatif. */
    case INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';

    /** Gagal validasi data lain (nominal, format, dll) sebelum ke DB. */
    case VALIDATION_ERROR = 'VALIDATION_ERROR';

    /** Error tidak terduga yang tidak masuk kategori di atas. */
    case UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    /**
     * Label ringkas untuk logging dan display.
     */
    public function label(): string
    {
        return match ($this) {
            self::WALLET_NOT_FOUND      => 'Dompet tidak ditemukan',
            self::CATEGORY_NOT_FOUND    => 'Kategori tidak ditemukan',
            self::INVALID_AMOUNT        => 'Nominal tidak valid',
            self::SAME_WALLET           => 'Dompet asal dan tujuan sama',
            self::INSUFFICIENT_BALANCE  => 'Saldo tidak mencukupi',
            self::VALIDATION_ERROR      => 'Validasi gagal',
            self::UNKNOWN_ERROR         => 'Error tidak diketahui',
        };
    }
}
