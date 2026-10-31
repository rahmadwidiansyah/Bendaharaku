<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menjadikan M3 sebagai tema UI default aplikasi.
 *
 * - Default kolom users.ui_theme berubah 'default' → 'm3' (user baru).
 * - Nilai 'default' yang ada dibackfill ke 'm3' sehingga seluruh user
 *   melihat M3; user tetap bisa kembali ke Default UI dari Settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'ui_theme')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('ui_theme', 20)->default('m3')->change();
            });

            DB::table('users')->where('ui_theme', 'default')->update(['ui_theme' => 'm3']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'ui_theme')) {
            DB::table('users')->where('ui_theme', 'm3')->update(['ui_theme' => 'default']);

            Schema::table('users', function (Blueprint $table) {
                $table->string('ui_theme', 20)->default('default')->change();
            });
        }
    }
};
