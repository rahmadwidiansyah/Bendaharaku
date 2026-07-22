<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan field hasil resolver ke tabel evidences.
     *
     * resolved_data berisi JSON dengan format TransactionDraft DTO.
     * resolver_warnings berisi array of warning strings.
     */
    public function up(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->json('resolved_data')->nullable()->after('parser_confidence');
            $table->string('resolver_engine', 30)->nullable()->after('resolved_data');
            $table->string('resolver_version', 20)->nullable()->after('resolver_engine');
            $table->float('resolver_confidence', 5, 4)->nullable()->after('resolver_version');
            $table->json('resolver_warnings')->nullable()->after('resolver_confidence');
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->dropColumn([
                'resolved_data',
                'resolver_engine',
                'resolver_version',
                'resolver_confidence',
                'resolver_warnings',
            ]);
        });
    }
};
