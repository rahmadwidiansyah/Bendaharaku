<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom ui_theme ke tabel users.
     *
     * ui_theme — pilihan sistem UI user (Multi-UI System Phase 1).
     *           Nilai valid: 'default', 'm3'. Default = 'default'
     *           sehingga pengguna lama/baru tetap memakai UI Default
     *           bila belum memilih tema.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'ui_theme')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('ui_theme', 20)->default('default')->after('category_icon_colored');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ui_theme');
        });
    }
};
