<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel conversations — satu percakapan chat per konteks.
 *
 * Arsitektur sengaja mendukung multiple conversations per user
 * meskipun phase awal hanya satu active conversation per user.
 *
 * Future features yang sudah dipersiapkan:
 * - Multiple conversations (sudah: user_id FK + is_active)
 * - Rename conversation (sudah: title nullable)
 * - Archive conversation (sudah: archived_at)
 * - Delete conversation (sudah: deleted_at soft delete)
 * - Metadata untuk AI memory per conversation (sudah: metadata JSON)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Judul percakapan — diisi user atau auto-generated dari pesan pertama
            $table->string('title')->nullable();

            // Hanya satu conversation yang active per user di phase awal
            $table->boolean('is_active')->default(true)->index();

            // Soft-delete untuk archive tanpa menghapus data
            $table->timestamp('archived_at')->nullable()->index();
            $table->softDeletes();

            // Metadata fleksibel: AI memory hint, conversation mode, dsb
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Index untuk query umum: ambil conversation aktif user tertentu
            $table->index(['user_id', 'is_active', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
