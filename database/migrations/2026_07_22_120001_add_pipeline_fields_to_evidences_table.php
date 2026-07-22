<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan field pipeline processing ke tabel evidences.
     *
     * Field ini mendukung lifecycle: UPLOADED → QUEUED → PROCESSING → OCR_COMPLETED → CLASSIFIED → PARSED → READY | FAILED
     */
    public function up(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->timestamp('processing_started_at')->nullable()->after('source');
            $table->timestamp('processing_finished_at')->nullable()->after('processing_started_at');
            $table->text('error_message')->nullable()->after('processing_finished_at');
            $table->unsignedInteger('retry_count')->default(0)->after('error_message');
            $table->timestamp('last_processed_at')->nullable()->after('retry_count');
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->dropColumn([
                'processing_started_at',
                'processing_finished_at',
                'error_message',
                'retry_count',
                'last_processed_at',
            ]);
        });
    }
};
