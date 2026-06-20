<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_feedback_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parse_log_id')->unique()->constrained('ai_parse_logs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('original_payload');
            $table->json('corrected_payload');
            $table->decimal('divergence_score', 5, 4)->default(0.0000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feedback_logs');
    }
};