<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ai_memory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memory_id')->nullable()->constrained('user_ai_memories')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // CREATED, REWARDED, DECAYED, UPDATED, PRUNED, DELETED, CONFLICT, MERGE
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('source')->nullable();
            $table->string('raw_subject')->nullable();
            $table->string('normalized_subject')->nullable();
            $table->string('memory_keyword')->nullable();
            $table->float('old_weight')->nullable();
            $table->float('new_weight')->nullable();
            $table->integer('old_hit_count')->nullable();
            $table->integer('new_hit_count')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->string('algorithm_version')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['memory_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_memory_logs');
    }
};
