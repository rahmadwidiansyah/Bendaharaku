<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_ai_memories', function (Blueprint $table) {
            $table->string('raw_subject')->nullable()->after('keyword_pattern');
            $table->string('normalized_subject')->nullable()->after('raw_subject');
            $table->string('memory_keyword')->nullable()->after('normalized_subject');
            $table->index('memory_keyword');
        });
    }

    public function down(): void
    {
        Schema::table('user_ai_memories', function (Blueprint $table) {
            $table->dropIndex(['memory_keyword']);
            $table->dropColumn('memory_keyword');
            $table->dropColumn('normalized_subject');
            $table->dropColumn('raw_subject');
        });
    }
};
