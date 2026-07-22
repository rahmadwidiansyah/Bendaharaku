<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Log Hasil Parsing (Untuk Audit & Dataset)
        Schema::create('ai_parse_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('model', 100);
            $table->text('input_text');
            $table->json('raw_response')->nullable();
            $table->decimal('confidence', 3, 2)->default(0.00); // 0.00 sampai 1.00
            $table->boolean('is_success');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // Tabel Log Penggunaan Token/Biaya (Untuk BYOK Tracking)
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('model', 100);
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_parse_logs');
    }
};
