<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persona asisten per-user (lapis B arsitektur assistant).
 *
 * bot_persona JSON nullable:
 * { tone, formality, response_length, emoji, custom_instructions }
 * null = persona default dari aplikasi (PersonaDefaults).
 * Tidak pernah menyimpan core system instructions (lapis A).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('bot_persona')->nullable()->after('bot_avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bot_persona');
        });
    }
};
