<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            // Simpan ringkasan bulan sebelumnya untuk quick reference
            $table->longText('previous_month_summary')->nullable()->after('ai_summary');

            // JSON comparison metrics: income_diff, expense_diff, net_diff, trend
            $table->json('comparison_metrics')->nullable()->after('metrics');

            // Status laporan (draft, completed, archived)
            $table->enum('status', ['draft', 'completed', 'archived'])->default('completed')->after('comparison_metrics');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['previous_month_summary', 'comparison_metrics', 'status']);
        });
    }
};
