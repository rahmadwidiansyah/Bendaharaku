<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('budget_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('period_month');
            $table->integer('period_year');
            $table->decimal('total_budget_amount', 15, 2);
            $table->text('ai_notes')->nullable();
            $table->string('generated_by'); // 'ai' or 'manual'
            $table->timestamps();

            $table->unique(['user_id', 'period_month', 'period_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_groups');
    }
};
