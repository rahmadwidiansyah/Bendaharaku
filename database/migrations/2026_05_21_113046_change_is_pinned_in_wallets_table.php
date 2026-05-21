<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->boolean('is_pinned')->nullable()->default(null)->change();
        });

        \Illuminate\Support\Facades\DB::table('wallets')->where('is_pinned', false)->update(['is_pinned' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('wallets')->whereNull('is_pinned')->update(['is_pinned' => false]);

        Schema::table('wallets', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->nullable(false)->change();
        });
    }
};
