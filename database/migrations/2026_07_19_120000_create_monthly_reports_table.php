<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('period_month');
            $table->string('title');
            $table->text('local_summary');
            $table->longText('ai_summary')->nullable();
            $table->longText('final_summary');
            $table->json('metrics')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period_month']);
            $table->index(['user_id', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};
