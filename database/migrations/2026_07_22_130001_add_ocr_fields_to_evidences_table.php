<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan field hasil OCR ke tabel evidences.
     *
     * Field ini menyimpan output dari OCR Microservice (PaddleOCR).
     * Belum ada parser — hanya plain text.
     */
    public function up(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->text('ocr_text')->nullable()->after('last_processed_at');
            $table->string('ocr_engine', 30)->nullable()->after('ocr_text');
            $table->unsignedInteger('ocr_duration_ms')->nullable()->after('ocr_engine');
            $table->string('ocr_version', 20)->nullable()->after('ocr_duration_ms');
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->dropColumn(['ocr_text', 'ocr_engine', 'ocr_duration_ms', 'ocr_version']);
        });
    }
};
