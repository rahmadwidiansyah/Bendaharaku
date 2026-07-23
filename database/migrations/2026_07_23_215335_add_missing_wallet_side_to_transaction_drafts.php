<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_drafts', function (Blueprint $table) {
            $table->string('missing_wallet_side', 20)->nullable()->after('payload');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_drafts', function (Blueprint $table) {
            $table->dropColumn('missing_wallet_side');
        });
    }
};
