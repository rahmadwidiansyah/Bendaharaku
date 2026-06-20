<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ai_daily_metrics'); // Buang schema lama

        Schema::create('ai_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('provider', 50);

            // Volume Metrics
            $table->integer('total_requests')->default(0);
            $table->integer('total_success')->default(0);
            $table->integer('total_drafts')->default(0);
            $table->integer('total_corrections')->default(0);

            // Performance Metrics
            $table->decimal('avg_raw_confidence', 5, 4)->default(0.0000);
            $table->decimal('avg_final_confidence', 5, 4)->default(0.0000);

            // Cost & Token Metrics
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('estimated_cost_usd', 10, 6)->default(0.000000);

            $table->timestamps();

            // Constraint: 1 User, 1 Hari, 1 Provider = 1 Baris Agregasi
            $table->unique(['user_id', 'date', 'provider'], 'ai_daily_metrics_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_daily_metrics');
    }
};