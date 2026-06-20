<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_training_samples', function (Blueprint $table) {
            $table->string('provider', 50)->after('input_text');
            $table->string('model', 100)->after('provider');
            $table->decimal('confidence', 5, 4)->after('model');
            $table->json('original_prediction')->after('confidence');
        });
    }

    public function down(): void
    {
        Schema::table('ai_training_samples', function (Blueprint $table) {
            $table->dropColumn(['provider', 'model', 'confidence', 'original_prediction']);
        });
    }
};