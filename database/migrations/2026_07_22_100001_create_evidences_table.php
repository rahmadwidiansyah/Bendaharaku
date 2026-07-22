<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Evidence table — menyimpan metadata file bukti (gambar) yang di-upload user.
     *
     * Alur: User upload → file tersimpan di storage/private/evidence/{user_id}/
     *       → record ini dibuat → status UPLOADED.
     *
     * Kolom OCR/AI/Parser BELUM diimplementasikan — sprint berikutnya.
     *
     * @see app/Models/Evidence.php
     */
    public function up(): void
    {
        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('mime_type', 50);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size');
            $table->string('disk', 30)->default('evidence');
            $table->string('path', 500);
            $table->string('status', 20)->default('UPLOADED');
            $table->string('source', 30)->default('CHAT_UPLOAD');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence');
    }
};
