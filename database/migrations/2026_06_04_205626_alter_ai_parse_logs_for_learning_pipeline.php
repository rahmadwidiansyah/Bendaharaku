<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_parse_logs', function (Blueprint $table) {
            // Unik untuk memastikan prinsip 1 Parse Log = 1 Pasangan Transaksi Logis
            $table->foreignId('transaction_log_id')
                  ->nullable()
                  ->unique()
                  ->after('user_id')
                  ->constrained('transaction_logs')
                  ->nullOnDelete();
                  
            $table->string('status', 50)->default('parsed')->index()->after('is_success');
            $table->integer('prompt_tokens')->default(0)->after('error_message');
            $table->integer('completion_tokens')->default(0)->after('prompt_tokens');
            $table->integer('total_tokens')->default(0)->after('completion_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('ai_parse_logs', function (Blueprint $table) {
            $table->dropForeign(['transaction_log_id']);
            $table->dropColumn([
                'transaction_log_id', 
                'status',
                'prompt_tokens', 
                'completion_tokens', 
                'total_tokens'
            ]);
        });
    }
};