<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('account_id')->nullable()->after('model');
            $table->string('trace_id', 50)->nullable()->after('account_id');
            $table->integer('attempt_count')->nullable()->after('trace_id');
            $table->string('selection_method', 30)->nullable()->after('attempt_count');
            $table->index(['user_id', 'provider', 'model']);
        });

        Schema::table('ai_parse_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('account_id')->nullable()->after('model');
            $table->string('trace_id', 50)->nullable()->after('account_id');
            $table->string('selection_method', 30)->nullable()->after('trace_id');
            $table->json('routing_attempts')->nullable()->after('selection_method');
            $table->string('fallback_from', 200)->nullable()->after('routing_attempts');
            $table->string('fallback_to', 200)->nullable()->after('fallback_from');
            $table->string('fallback_reason', 100)->nullable()->after('fallback_to');
        });
    }

    public function down(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table): void {
            $table->dropColumn(['account_id', 'trace_id', 'attempt_count', 'selection_method']);
        });
        Schema::table('ai_parse_logs', function (Blueprint $table): void {
            $table->dropColumn(['account_id', 'trace_id', 'selection_method', 'routing_attempts', 'fallback_from', 'fallback_to', 'fallback_reason']);
        });
    }
};
