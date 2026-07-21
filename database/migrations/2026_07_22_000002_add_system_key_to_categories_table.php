<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('system_key')->nullable()->after('type_id');
            $table->string('custom_name')->nullable()->after('category_name');
            $table->string('custom_icon')->nullable()->after('icon');
        });

        // Backfill existing system categories
        DB::table('categories')
            ->whereIn('category_name', ['Pindah Saldo', 'Transfer Saldo'])
            ->update(['system_key' => 'TRANSFER']);

        DB::table('categories')
            ->where('category_name', 'Dapat Hutangan')
            ->update(['system_key' => 'LOAN']);

        DB::table('categories')
            ->where('category_name', 'Bayar Cicilan Hutang')
            ->update(['system_key' => 'DEBT_PAYMENT']);

        DB::table('categories')
            ->where('category_name', 'Ngasih Piutang')
            ->update(['system_key' => 'RECEIVABLE']);

        DB::table('categories')
            ->where('category_name', 'Terima Bayar Piutang')
            ->update(['system_key' => 'RECEIVABLE_PAYMENT']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['system_key', 'custom_name', 'custom_icon']);
        });
    }
};
