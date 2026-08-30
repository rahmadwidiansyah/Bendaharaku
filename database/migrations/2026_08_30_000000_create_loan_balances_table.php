<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->string('loan_type', 20);
            $table->decimal('balance', 15, 2)->default(0);
            $table->date('opened_at')->nullable();
            $table->date('last_transaction_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'subject', 'loan_type']);
            $table->index(['user_id', 'loan_type', 'balance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_balances');
    }
};
