<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('provider', 50);
            $table->integer('total_parse')->default(0);
            $table->integer('draft_count')->default(0); // Penambahan metrik akurasi mode draft
            $table->decimal('success_rate', 5, 4)->default(0.0000);
            $table->decimal('correction_rate', 5, 4)->default(0.0000);
            $table->integer('token_usage')->default(0);
            $table->decimal('est_cost', 10, 6)->default(0.000000);
            $table->timestamps();

            $table->unique(['user_id', 'date', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_daily_metrics');
    }
};
