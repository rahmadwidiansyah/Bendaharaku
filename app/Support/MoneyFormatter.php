<?php

declare(strict_types=1);

namespace App\Support;

/**
 * MoneyFormatter — satu-satunya tempat formatting uang di seluruh aplikasi.
 *
 * Semua platform (Telegram, Web, WhatsApp, Email, dll) harus memanggil class ini
 * agar format tampilan uang konsisten dan tidak ada konversi float tersebar di mana-mana.
 *
 * Contract:
 *  - Menerima int|float. Tidak menerima string.
 *  - Mengembalikan string siap tampil.
 *
 * Kenapa tidak menerima string?
 *  - Jika string lolos sampai ke sini, berarti ada layer upstream yang belum di-cast.
 *  - Lebih baik throw TypeError lebih awal agar mudah dideteksi saat development.
 */
class MoneyFormatter
{
    /**
     * Format angka menjadi string Rupiah tanpa simbol "Rp".
     *
     * Contoh: 102100.0 → "102.100"
     */
    public static function amount(int|float $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }

    /**
     * Format angka menjadi string Rupiah lengkap dengan prefix "Rp ".
     *
     * Contoh: 102100.0 → "Rp 102.100"
     */
    public static function rupiah(int|float $amount): string
    {
        return 'Rp ' . self::amount($amount);
    }

    /**
     * Format angka menjadi string Rupiah tanpa spasi setelah "Rp".
     * Dipakai di Telegram Markdown agar tidak ada spasi ganjil.
     *
     * Contoh: 20000.0 → "Rp20.000"
     */
    public static function rupiahCompact(int|float $amount): string
    {
        return 'Rp' . self::amount($amount);
    }

    /**
     * Format angka dengan desimal (untuk tampilan yang perlu presisi).
     *
     * Contoh: 102100.5 → "Rp 102.100,50"
     */
    public static function rupiahDecimal(int|float $amount, int $decimals = 2): string
    {
        return 'Rp ' . number_format($amount, $decimals, ',', '.');
    }
}
