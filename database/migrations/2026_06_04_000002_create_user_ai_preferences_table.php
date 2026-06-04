<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ai_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('selected_model', 100);
            $table->boolean('is_active_provider')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX user_single_active_provider_idx ON user_ai_preferences (user_id) WHERE (is_active_provider = true)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_preferences');
    }
};
