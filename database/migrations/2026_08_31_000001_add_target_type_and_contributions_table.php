<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_ai_memories', function (Blueprint $table) {
            $table->string('target_type', 20)->default('category')->after('memory_keyword');
            $table->index(['user_id', 'target_type', 'keyword_pattern'], 'uam_user_type_keyword');
        });

        Schema::create('user_ai_memory_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memory_id')->constrained('user_ai_memories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('source', 30)->nullable();
            $table->string('keyword');
            $table->string('target_type', 20);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_name')->nullable();
            $table->float('weight_delta')->default(1.0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['transaction_id', 'is_active'], 'uamc_txn_active');
            $table->index(['memory_id', 'is_active'], 'uamc_mem_active');
            $table->index(['user_id', 'target_type'], 'uamc_user_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_memory_contributions');

        Schema::table('user_ai_memories', function (Blueprint $table) {
            $table->dropIndex('uam_user_type_keyword');
            $table->dropColumn('target_type');
        });
    }
};
