<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel chat_messages — satu baris = satu pesan dalam percakapan.
 *
 * role: 'user' | 'assistant'
 *   - user     → pesan dari user
 *   - assistant → respons dari AI/bot
 *
 * content: JSON array ChatComponentInterface::toArray()
 *   Format: [{ type: 'text', ... }, { type: 'transaction_card', ... }]
 *   Ini memungkinkan frontend merender ulang respons bot secara akurat
 *   tanpa re-processing, termasuk TransactionCard, ErrorCard, dsb.
 *
 * raw_text: teks mentah dari user (untuk logging + search + AI memory)
 *
 * metadata: trace_id, provider, model, confidence, latency_ms, dsb.
 *   Dipakai untuk audit dan debugging.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();

            // 'user' atau 'assistant' — mengikuti konvensi OpenAI / LLM standar
            $table->string('role', 20)->index();

            // Array komponen JSON untuk render frontend (platform-agnostic)
            $table->json('content');

            // Teks mentah (pesan user) atau teks plain (ringkasan bot untuk memory)
            $table->text('raw_text')->nullable();

            // Observability: trace_id, provider, model, confidence, latency_ms
            $table->json('metadata')->nullable();

            // Soft delete agar pesan bisa dihapus user tanpa kehilangan data audit
            $table->softDeletes();

            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            // Index untuk pagination chat history (conversation + urutan waktu)
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
