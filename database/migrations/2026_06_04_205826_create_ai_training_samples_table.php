<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_training_samples', function (Blueprint $table) {
            $table->id();
            $table->string('hash_signature', 64)->unique();
            $table->string('source', 50)->index();
            $table->text('input_text');
            $table->json('expected_json');
            $table->decimal('quality_score', 5, 4)->default(0.0000);
            $table->string('status', 50)->default('raw')->index();
            $table->boolean('is_verified')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_training_samples');
    }
};
