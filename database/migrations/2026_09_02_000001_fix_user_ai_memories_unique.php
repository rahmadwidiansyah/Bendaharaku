<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "user_ai_memories" DROP CONSTRAINT IF EXISTS "user_ai_memories_user_id_keyword_pattern_unique"');
        DB::statement('DROP INDEX IF EXISTS "user_ai_memories_user_id_keyword_pattern_unique"');

        Schema::table('user_ai_memories', function (Blueprint $table) {
            $exists = collect(DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'user_ai_memories' AND indexname = 'uam_user_keyword_target_unique'"))->isNotEmpty();
            if (! $exists) {
                $table->unique(['user_id', 'keyword_pattern', 'target_type'], 'uam_user_keyword_target_unique');
            }
        });
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "user_ai_memories" DROP CONSTRAINT IF EXISTS "uam_user_keyword_target_unique"');
        DB::statement('DROP INDEX IF EXISTS "uam_user_keyword_target_unique"');

        Schema::table('user_ai_memories', function (Blueprint $table) {
            $exists = collect(DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'user_ai_memories' AND indexname = 'user_ai_memories_user_id_keyword_pattern_unique'"))->isNotEmpty();
            if (! $exists) {
                $table->unique(['user_id', 'keyword_pattern']);
            }
        });
    }
};
