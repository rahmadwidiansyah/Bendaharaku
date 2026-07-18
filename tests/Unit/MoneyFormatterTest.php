<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\MoneyFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Unit test untuk MoneyFormatter.
 *
 * Test ini TIDAK membutuhkan database atau Laravel container —
 * semua method MoneyFormatter adalah pure static function.
 *
 * Coverage:
 *  - Setiap method menerima int dan float
 *  - Angka bulat, angka dengan desimal, nol, angka besar
 *  - Format output sesuai standar Rupiah Indonesia (titik sebagai pemisah ribuan)
 *  - Type contract: string harus ditolak (TypeError)
 */
class MoneyFormatterTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════════
    // amount()
    // ══════════════════════════════════════════════════════════════════

    public function test_amount_formats_integer(): void
    {
        $this->assertSame('102.100', MoneyFormatter::amount(102100));
    }

    public function test_amount_formats_float_as_returned_by_postgresql_pdo(): void
    {
        // Skenario utama perbaikan:
        // PostgreSQL DECIMAL(15,2) → PDO mengembalikan string "102100.00"
        // → Model $casts=['balance'=>'float'] mengubahnya ke float
        // → MoneyFormatter menerima float, bukan string
        $this->assertSame('102.100', MoneyFormatter::amount(102100.00));
    }

    public function test_amount_rounds_decimals_at_zero_precision(): void
    {
        // number_format() melakukan ROUNDING, bukan truncation.
        // 15000.99 dibulatkan ke 15001
        $this->assertSame('15.001', MoneyFormatter::amount(15000.99));
        // Nilai yang tidak menyebabkan rounding tetap benar
        $this->assertSame('15.000', MoneyFormatter::amount(15000.00));
        $this->assertSame('15.000', MoneyFormatter::amount(15000.10));
    }

    public function test_amount_formats_zero(): void
    {
        $this->assertSame('0', MoneyFormatter::amount(0));
    }

    public function test_amount_formats_small_value(): void
    {
        $this->assertSame('5.000', MoneyFormatter::amount(5000));
    }

    public function test_amount_formats_large_gaji(): void
    {
        $this->assertSame('5.000.000', MoneyFormatter::amount(5000000));
    }

    public function test_amount_formats_hundred_thousand(): void
    {
        $this->assertSame('100.000', MoneyFormatter::amount(100000));
    }

    // ══════════════════════════════════════════════════════════════════
    // rupiah()
    // ══════════════════════════════════════════════════════════════════

    public function test_rupiah_prepends_rp_with_space(): void
    {
        $this->assertSame('Rp 102.100', MoneyFormatter::rupiah(102100));
    }

    public function test_rupiah_works_with_float_from_pdo(): void
    {
        $this->assertSame('Rp 102.100', MoneyFormatter::rupiah(102100.00));
    }

    public function test_rupiah_formats_zero(): void
    {
        $this->assertSame('Rp 0', MoneyFormatter::rupiah(0));
    }

    public function test_rupiah_formats_gaji(): void
    {
        $this->assertSame('Rp 5.000.000', MoneyFormatter::rupiah(5000000));
    }

    public function test_rupiah_formats_small_jajan(): void
    {
        $this->assertSame('Rp 15.000', MoneyFormatter::rupiah(15000));
    }

    // ══════════════════════════════════════════════════════════════════
    // rupiahCompact()
    // ══════════════════════════════════════════════════════════════════

    public function test_rupiah_compact_has_no_space_after_rp(): void
    {
        // Dipakai di Telegram Markdown agar bold-wrap *Rp20.000* tidak ada spasi ganjil
        $this->assertSame('Rp102.100', MoneyFormatter::rupiahCompact(102100));
    }

    public function test_rupiah_compact_works_with_float(): void
    {
        $this->assertSame('Rp20.000', MoneyFormatter::rupiahCompact(20000.00));
    }

    public function test_rupiah_compact_formats_zero(): void
    {
        $this->assertSame('Rp0', MoneyFormatter::rupiahCompact(0));
    }

    // ══════════════════════════════════════════════════════════════════
    // rupiahDecimal()
    // ══════════════════════════════════════════════════════════════════

    public function test_rupiah_decimal_shows_two_decimal_places_by_default(): void
    {
        $this->assertSame('Rp 102.100,50', MoneyFormatter::rupiahDecimal(102100.50));
    }

    public function test_rupiah_decimal_respects_custom_precision(): void
    {
        $this->assertSame('Rp 102.100,5000', MoneyFormatter::rupiahDecimal(102100.50, 4));
    }

    public function test_rupiah_decimal_pads_missing_decimals(): void
    {
        $this->assertSame('Rp 100.000,00', MoneyFormatter::rupiahDecimal(100000.0));
    }

    // ══════════════════════════════════════════════════════════════════
    // Type contract — string HARUS ditolak
    // ══════════════════════════════════════════════════════════════════

    public function test_amount_rejects_string_with_type_error(): void
    {
        // Contract utama MoneyFormatter: string tidak boleh diterima.
        // Jika string sampai ke sini, artinya model cast di layer upstream belum benar.
        // TypeError lebih awal = bug lebih mudah dideteksi.
        $this->expectException(\TypeError::class);

        // @phpstan-ignore-next-line intentional invalid type for test
        MoneyFormatter::amount('102100.00');
    }

    public function test_rupiah_rejects_string_with_type_error(): void
    {
        $this->expectException(\TypeError::class);

        // @phpstan-ignore-next-line
        MoneyFormatter::rupiah('5000');
    }
}
