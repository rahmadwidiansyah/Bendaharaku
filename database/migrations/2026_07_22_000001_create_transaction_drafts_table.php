<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel transaction_drafts — Staging area untuk hasil parsing AI Chat.
 *
 * Filosofi arsitektur:
 * - Draft adalah PREVIEW murni, bukan transaksi keuangan.
 * - Draft TIDAK PERNAH mempengaruhi saldo wallet, ledger, atau statistik.
 * - Transaksi keuangan nyata hanya dibuat saat user menekan Konfirmasi.
 * - Setelah konfirmasi, draft di-delete (atau status = confirmed untuk audit trail).
 *
 * Mengapa terpisah dari transaction_logs:
 * 1. Separation of concern: data keuangan hanya di transaction_logs.
 * 2. Mencegah draft "bocor" ke dashboard/laporan/saldo meski ada bug di query.
 * 3. Mudah di-purge: expired drafts tidak mengotori tabel utama.
 * 4. Payload JSON fleksibel: draft bisa berisi multi-transaksi dalam 1 baris.
 * 5. Idempotency: confirmed_transaction_id mencegah double commit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_drafts', function (Blueprint $table) {
            $table->id();

            // Pemilik draft
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // Conversation/session chat dari mana draft ini berasal
            // Nullable: draft dari Telegram tidak punya conversation_id
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->foreign('conversation_id')->references('id')->on('conversations')->nullOnDelete();

            // Provider AI yang menghasilkan parsing ini
            $table->string('ai_provider', 50)->nullable();
            $table->string('ai_model', 100)->nullable();

            // Tipe draft: 'single' atau 'multi'
            $table->string('draft_type', 20)->default('single');

            // Payload lengkap hasil parsing AI.
            // Untuk single: {...}
            // Untuk multi: [{...}, {...}]
            // Berisi semua field: amount, category, wallet, type, notes, subject, dll.
            $table->jsonb('payload');

            // Status life-cycle draft
            // pending   → menunggu konfirmasi user
            // confirmed → sudah dikonfirmasi, transaksi telah dibuat
            // cancelled → dibatalkan user
            // expired   → kedaluwarsa (di-cleanup oleh scheduler)
            $table->string('status', 20)->default('pending');

            // ID transaksi yang dibuat setelah konfirmasi.
            // - Null jika belum dikonfirmasi.
            // - Diisi setelah commit sukses → mencegah double commit (idempotency guard).
            // - Untuk multi-transaksi: simpan array ID sebagai JSON di sini.
            $table->jsonb('confirmed_transaction_ids')->nullable();

            // Waktu kedaluwarsa draft. Default: 24 jam setelah dibuat.
            // Draft yang expired akan dibersihkan oleh scheduler.
            $table->timestamp('expires_at')->nullable();

            // Confidence score dari AI (0.0–1.0) untuk tampilan di UI
            $table->decimal('ai_confidence', 4, 3)->nullable();

            // Teks asli dari user (untuk debug/audit)
            $table->text('original_text')->nullable();

            $table->timestamps();

            // Index untuk query chat loading dan cleanup scheduler
            $table->index(['user_id', 'status', 'expires_at']);
            $table->index(['conversation_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_drafts');
    }
};
