<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan field commit ke tabel evidences.
     *
     * Field:
     * - transaction_id: foreign key ke transaction_logs (nullable)
     * - completed_at: timestamp saat evidence berhasil di-commit (nullable)
     * - commit_version: versi pipeline saat commit (nullable)
     */
    public function up(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->foreignId('transaction_id')->nullable()->after('resolver_warnings')->constrained('transaction_logs')->nullOnDelete();
            $table->timestamp('completed_at')->nullable()->after('transaction_id');
            $table->string('commit_version', 20)->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropColumn([
                'transaction_id',
                'completed_at',
                'commit_version',
            ]);
        });
    }
};
