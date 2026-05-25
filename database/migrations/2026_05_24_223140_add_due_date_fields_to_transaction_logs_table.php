<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_logs', function (Blueprint $table) {
            $table->date('due_date')->nullable();
            $table->string('due_date_type')->nullable(); // 'fixed', 'monthly', 'daily'
            $table->integer('due_date_interval')->nullable(); // e.g., 15 for 15th of the month, or 7 for every 7 days
        });
    }

    public function down(): void
    {
        Schema::table('transaction_logs', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'due_date_type', 'due_date_interval']);
        });
    }
};
