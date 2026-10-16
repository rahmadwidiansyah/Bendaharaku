<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceIndexesTest extends TestCase
{
    public function test_performance_indexes_exist()
    {
        $this->assertTrue(Schema::hasTable('transaction_logs'));

        $names = collect(DB::select("SELECT indexname FROM pg_indexes WHERE schemaname = 'public'"))
            ->pluck('indexname')->all();

        foreach ([
            'transaction_logs_user_type_date_index',
            'wallets_user_group_index',
            'categories_user_system_key_index',
            'categories_user_type_index',
            'transaction_logs_subject_trgm_index',
            'transaction_logs_notes_trgm_index',
            'categories_name_trgm_index',
        ] as $expected) {
            $this->assertContains($expected, $names, "Indeks {$expected} tidak ditemukan.");
        }
    }
}
