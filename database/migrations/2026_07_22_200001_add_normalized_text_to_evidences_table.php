<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->text('normalized_text')->nullable()->after('ocr_text');
            $table->integer('normalization_duration_ms')->nullable()->after('normalized_text');
            $table->integer('normalization_changes')->nullable()->after('normalization_duration_ms');
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->dropColumn([
                'normalized_text',
                'normalization_duration_ms',
                'normalization_changes',
            ]);
        });
    }
};
