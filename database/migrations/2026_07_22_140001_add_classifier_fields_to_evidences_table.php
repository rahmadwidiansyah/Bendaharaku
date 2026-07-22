<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan field hasil Document Classifier ke tabel evidences.
     *
     * Field ini menyimpan output dari DocumentClassifier (rule-based).
     * Belum ada parser — hanya jenis dokumen dan confidence.
     */
    public function up(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->string('document_type', 30)->nullable()->after('ocr_version');
            $table->string('classifier_engine', 30)->nullable()->after('document_type');
            $table->string('classifier_version', 20)->nullable()->after('classifier_engine');
            $table->float('classifier_confidence', 5, 4)->nullable()->after('classifier_version');
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->dropColumn([
                'document_type',
                'classifier_engine',
                'classifier_version',
                'classifier_confidence',
            ]);
        });
    }
};
