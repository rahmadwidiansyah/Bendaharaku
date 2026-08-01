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
        Schema::create('budget_expense_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_group_id')->constrained()->cascadeOnDelete();
            $table->string('group_key');
            $table->string('group_name');
            $table->json('category_ids');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_expense_groups');
    }
};
