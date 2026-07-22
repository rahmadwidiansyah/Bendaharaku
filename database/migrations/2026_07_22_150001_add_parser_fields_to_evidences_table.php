<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan field hasil parser ke tabel evidences.
     *
     * Field ini menyimpan output dari parser (TransferReceiptParser, dll).
     * parsed_data berisi JSON dengan format EvidenceData DTO.
     */
    public function up(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->json('parsed_data')->nullable()->after('classifier_confidence');
            $table->string('parser_engine', 30)->nullable()->after('parsed_data');
            $table->string('parser_version', 20)->nullable()->after('parser_engine');
            $table->float('parser_confidence', 5, 4)->nullable()->after('parser_version');
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->dropColumn(['parsed_data', 'parser_engine', 'parser_version', 'parser_confidence']);
        });
    }
};
