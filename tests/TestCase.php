<?php

namespace Tests;

use App\Support\TransactionTypeIds;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Full-suite sering dijalankan dengan `--env-file .env` yang memuat
        // CACHE_STORE=file; variabel env nyata menimpa `CACHE_STORE=array`
        // di phpunit.xml. Cache file tidak ikut rollback transaksi
        // RefreshDatabase, sehingga entry (mis. idempotency result dengan
        // TTL 24 jam) bocor lintas test dan lintas run → hasil flaky.
        // Samakan dengan niat phpunit.xml: store array, terisolasi per proses.
        config(['cache.default' => 'array']);

        // RefreshDatabase me-rollback transaksi per test; sequence id tipe
        // transaksi terus berlanjut. Memoize id lama membuat agregat tool
        // (SummarizesCashflow, dsb.) memfilter id yang sudah tidak valid.
        TransactionTypeIds::flush();
    }
}
