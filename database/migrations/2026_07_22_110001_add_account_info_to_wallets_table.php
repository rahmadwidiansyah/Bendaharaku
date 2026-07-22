<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom informasi rekening pada tabel wallets.
     *
     * Semua kolom bersifat nullable agar backward compatible.
     * Digunakan untuk: OCR Receipt, Transfer Resolver, QRIS, Auto Wallet Matching.
     */
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->string('account_name', 255)->nullable()->after('keyword');
            $table->string('account_number', 100)->nullable()->after('account_name');
            $table->string('bank_code', 50)->nullable()->after('account_number');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['account_name', 'account_number', 'bank_code']);
        });
    }
};
