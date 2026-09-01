<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_ai_memories', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'keyword_pattern']);
        });

        Schema::table('user_ai_memories', function (Blueprint $table) {
            $table->unique(['user_id', 'keyword_pattern', 'target_type'], 'uam_user_keyword_target_unique');
        });
    }

    public function down(): void
    {
        Schema::table('user_ai_memories', function (Blueprint $table) {
            $table->dropUnique('uam_user_keyword_target_unique');
        });

        Schema::table('user_ai_memories', function (Blueprint $table) {
            $table->unique(['user_id', 'keyword_pattern']);
        });
    }
};
