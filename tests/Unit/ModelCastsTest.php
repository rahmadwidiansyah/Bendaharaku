<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\TransactionLog;
use App\Models\Wallet;
use PHPUnit\Framework\TestCase;

/**
 * Unit test untuk memverifikasi $casts pada Model Wallet dan TransactionLog.
 *
 * ROOT CAUSE yang diperbaiki:
 * PostgreSQL DECIMAL(15,2) dikembalikan sebagai string oleh PHP PDO driver.
 * Eloquent Model HARUS mendefinisikan $casts agar nilai otomatis di-cast ke float.
 *
 * Test ini memverifikasi bahwa model MEMILIKI cast yang benar — sehingga
 * developer yang kelak menghapus cast tersebut akan langsung tahu dampaknya.
 *
 * Catatan: Test ini TIDAK perlu database — hanya memeriksa konfigurasi model.
 */
class ModelCastsTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════════
    // Wallet
    // ══════════════════════════════════════════════════════════════════

    public function test_wallet_has_balance_cast_to_float(): void
    {
        $wallet = new Wallet;
        $casts = $wallet->getCasts();

        $this->assertArrayHasKey('balance', $casts,
            'Wallet::$casts harus mendefinisikan "balance" agar PDO string dikonversi ke float.');
        $this->assertSame('float', $casts['balance'],
            'Wallet.balance harus di-cast ke float, bukan "'.($casts['balance'] ?? 'tidak ada').'".');
    }

    public function test_wallet_has_is_pinned_cast_to_boolean(): void
    {
        $wallet = new Wallet;
        $casts = $wallet->getCasts();

        $this->assertArrayHasKey('is_pinned', $casts);
        $this->assertSame('boolean', $casts['is_pinned']);
    }

    public function test_wallet_balance_cast_converts_decimal_string_to_float(): void
    {
        // Simulasi nilai yang dikembalikan oleh PDO PostgreSQL
        $wallet = new Wallet;
        $wallet->balance = '102100.00'; // string seperti dari PDO pgsql

        // Setelah set melalui Eloquent + cast, harus menjadi float
        $this->assertIsFloat($wallet->balance,
            'Setelah di-set string "102100.00", Wallet.balance harus menjadi float karena cast.');
        $this->assertSame(102100.0, $wallet->balance);
    }

    // ══════════════════════════════════════════════════════════════════
    // TransactionLog
    // ══════════════════════════════════════════════════════════════════

    public function test_transaction_log_has_amount_cast_to_float(): void
    {
        $trx = new TransactionLog;
        $casts = $trx->getCasts();

        $this->assertArrayHasKey('amount', $casts,
            'TransactionLog::$casts harus mendefinisikan "amount".');
        $this->assertSame('float', $casts['amount']);
    }

    public function test_transaction_log_has_balance_before_cast_to_float(): void
    {
        $trx = new TransactionLog;
        $casts = $trx->getCasts();

        $this->assertArrayHasKey('balance_before', $casts,
            'TransactionLog::$casts harus mendefinisikan "balance_before".');
        $this->assertSame('float', $casts['balance_before']);
    }

    public function test_transaction_log_has_balance_after_cast_to_float(): void
    {
        $trx = new TransactionLog;
        $casts = $trx->getCasts();

        $this->assertArrayHasKey('balance_after', $casts,
            'TransactionLog::$casts harus mendefinisikan "balance_after".');
        $this->assertSame('float', $casts['balance_after']);
    }

    public function test_transaction_log_has_is_cleared_cast_to_boolean(): void
    {
        $trx = new TransactionLog;
        $casts = $trx->getCasts();

        $this->assertArrayHasKey('is_cleared', $casts);
        $this->assertSame('boolean', $casts['is_cleared']);
    }

    public function test_transaction_log_amount_cast_converts_decimal_string_to_float(): void
    {
        $trx = new TransactionLog;
        $trx->amount = '15000.00'; // string dari PDO pgsql

        $this->assertIsFloat($trx->amount,
            'TransactionLog.amount harus menjadi float setelah di-set string dari PDO.');
        $this->assertSame(15000.0, $trx->amount);
    }

    public function test_transaction_log_balance_after_cast_converts_decimal_string_to_float(): void
    {
        $trx = new TransactionLog;
        $trx->balance_after = '85000.00';

        $this->assertIsFloat($trx->balance_after);
        $this->assertSame(85000.0, $trx->balance_after);
    }
}
