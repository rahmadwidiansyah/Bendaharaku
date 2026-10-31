<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * TransactionTypeIds — resolver id tipe transaksi dari database.
 *
 * Id pada tabel `transaction_types` adalah data yang di-generate seeder
 * (updateOrCreate tanpa id eksplisit), sehingga nilainya TIDAK boleh
 * di-hardcode di kode: reseed berulang tanpa reset sequence menggeser id
 * (mis. 1800+). Query agregat yang memfilter id hardcode akan diam-diam
 * menghasilkan nol. Helper ini meng-resolve id by name sekali per proses.
 */
class TransactionTypeIds
{
    /** @var array<string, int>|null name → id */
    private static ?array $ids = null;

    public static function income(): int
    {
        return self::get('Income');
    }

    public static function expense(): int
    {
        return self::get('Expense');
    }

    public static function transfer(): int
    {
        return self::get('Transfer');
    }

    public static function debt(): int
    {
        return self::get('Debt');
    }

    public static function receivable(): int
    {
        return self::get('Receivable');
    }

    public static function get(string $name): int
    {
        self::load();

        return self::$ids[$name] ?? 0;
    }

    private static function load(): void
    {
        if (self::$ids !== null) {
            return;
        }

        self::$ids = DB::table('transaction_types')->pluck('id', 'name')->all();
    }
}
