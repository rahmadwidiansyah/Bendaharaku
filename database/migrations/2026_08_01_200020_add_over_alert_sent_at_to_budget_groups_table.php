<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_groups', function (Blueprint $table) {
            $table->timestamp('over_alert_sent_at')->nullable()->after('generated_by');
        });
    }

    public function down(): void
    {
        Schema::table('budget_groups', function (Blueprint $table) {
            $table->dropColumn('over_alert_sent_at');
        });
    }
};
