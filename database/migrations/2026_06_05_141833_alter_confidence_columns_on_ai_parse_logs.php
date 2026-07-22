<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_parse_logs', function (Blueprint $table) {
            // Ubah nama kolom lama menjadi raw_confidence
            $table->renameColumn('confidence', 'raw_confidence');
        });

        Schema::table('ai_parse_logs', function (Blueprint $table) {
            // Tambahkan final_confidence
            $table->decimal('final_confidence', 5, 4)->default(0.0000)->after('raw_confidence');
        });
    }

    public function down(): void
    {
        Schema::table('ai_parse_logs', function (Blueprint $table) {
            $table->dropColumn('final_confidence');
        });

        Schema::table('ai_parse_logs', function (Blueprint $table) {
            $table->renameColumn('raw_confidence', 'confidence');
        });
    }
};
