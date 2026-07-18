<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom preferensi bahasa dan timezone untuk user.
     *
     * locale   — kode bahasa BCP-47: 'id', 'en', 'ja', dll.
     *            null berarti 'follow device / platform locale'.
     * timezone — IANA timezone: 'Asia/Jakarta', 'UTC', dll.
     *            null berarti 'Asia/Jakarta' (default app).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->after('allow_negative_balance')
                ->comment('BCP-47 locale. null = follow platform device.');
            $table->string('timezone', 50)->nullable()->after('locale')
                ->comment('IANA timezone. null = Asia/Jakarta default.');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'timezone']);
        });
    }
};
