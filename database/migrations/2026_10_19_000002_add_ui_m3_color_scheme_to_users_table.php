<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom ui_m3_color_scheme ke tabel users.
     *
     * ui_m3_color_scheme — skema warna Material 3 pilihan user (Multi-UI).
     *                      Nilai valid: 'teal', 'baseline', 'ocean',
     *                      'forest', 'sunset', 'rose', 'midnight'.
     *                      Default = 'teal' (selaras dengan aksen brand).
     *                      Preferensi ini INDEPENDEN dari accent_color
     *                      milik UI Default.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'ui_m3_color_scheme')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('ui_m3_color_scheme', 20)->default('teal')->after('ui_theme');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ui_m3_color_scheme');
        });
    }
};
