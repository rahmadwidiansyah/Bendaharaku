<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel evidence_processing_logs menyimpan riwayat setiap perpindahan status evidence.
     *
     * Setiap perubahan stage mencatat:
     * - stage: nama stage (UPLOAD, QUEUE, OCR, CLASSIFY, PARSE, RESOLVE, COMMIT)
     * - status_before: status sebelum perubahan
     * - status_after: status sesudah perubahan
     * - duration_ms: durasi proses stage ini (nullable)
     * - message: pesan error/sukses (nullable)
     * - metadata: JSON tambahan (engine version, dll)
     */
    public function up(): void
    {
        Schema::create('evidence_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')->constrained('evidence')->cascadeOnDelete();
            $table->string('stage', 30); // UPLOAD, QUEUE, OCR, CLASSIFY, PARSE, RESOLVE, COMMIT
            $table->string('status_before', 20)->nullable();
            $table->string('status_after', 20);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['evidence_id', 'created_at']);
            $table->index(['evidence_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_processing_logs');
    }
};
