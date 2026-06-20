<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ai_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('keyword_pattern')->index();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->integer('hit_count')->default(1);
            $table->float('weight')->default(1.0);
            $table->timestamp('last_applied_at')->nullable();
            $table->timestamps();

            // Mencegah ada dua keyword yang sama persis untuk user yang sama
            $table->unique(['user_id', 'keyword_pattern']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_memories');
    }
};