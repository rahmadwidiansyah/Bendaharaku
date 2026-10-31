<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log eksekusi tool assistant (read-only) untuk observability.
 *
 * Tidak menyimpan API key, payload hasil besar, atau teks user penuh.
 * params sudah disanitasi (hanya key yang dikenal tool).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_tool_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('chat_message_id')->nullable();
            $table->string('trace_id', 64)->index();
            $table->string('tool', 64);
            $table->json('params');
            $table->string('status', 16);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('result_rows')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_logs');
    }
};
