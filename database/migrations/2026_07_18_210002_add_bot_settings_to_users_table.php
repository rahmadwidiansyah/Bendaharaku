<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah pengaturan Bot Chat per-user ke tabel users.
 *
 * bot_name  — nama bot yang dipersonalisasi user (default: 'Ken-Chan')
 * bot_avatar — path ke avatar bot (storage/app/public/...)
 *              null berarti gunakan placeholder default
 *
 * Mengikuti pola existing columns di users table:
 * - avatar (foto profil user)
 * - locale
 * - timezone
 * - allow_negative_balance
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bot_name')->nullable()->after('avatar');
            $table->string('bot_avatar')->nullable()->after('bot_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bot_name', 'bot_avatar']);
        });
    }
};
