<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom accent_color ke tabel users.
     *
     * accent_color — pilihan warna aksen UI user.
     *               Nilai valid: purple, blue, green, orange, red, pink
     *               null / default = 'purple'
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('accent_color', 20)->nullable()->after('date_format')
                ->comment('Warna aksen UI pilihan user. null = purple (default).');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('accent_color');
        });
    }
};
