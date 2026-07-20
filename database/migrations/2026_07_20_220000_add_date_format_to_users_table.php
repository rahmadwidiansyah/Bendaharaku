<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom date_format ke tabel users.
     *
     * date_format — format tampilan tanggal pilihan user.
     *               Nilai valid: DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD
     *               null berarti pakai default DD/MM/YYYY.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('date_format', 20)->nullable()->after('timezone')
                ->comment('Format tanggal preferensi user. null = DD/MM/YYYY.');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('date_format');
        });
    }
};
