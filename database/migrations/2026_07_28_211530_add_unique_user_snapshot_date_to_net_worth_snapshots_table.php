<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bersihkan duplikat (user_id, snapshot_date) dulu SEBELUM pasang unique
        // constraint — kalau ada baris duplikat dari bug lama, unique() bakal gagal.
        // Simpan baris dengan id TERBESAR (asumsi: hasil hitung paling baru/benar).
        $duplicateIds = DB::table('net_worth_snapshots as a')
            ->join('net_worth_snapshots as b', function ($join) {
                $join->on('a.user_id', '=', 'b.user_id')
                    ->on('a.snapshot_date', '=', 'b.snapshot_date')
                    ->whereColumn('a.id', '<', 'b.id');
            })
            ->pluck('a.id');

        if ($duplicateIds->isNotEmpty()) {
            DB::table('net_worth_snapshots')->whereIn('id', $duplicateIds)->delete();
        }

        Schema::table('net_worth_snapshots', function (Blueprint $table) {
            $table->unique(['user_id', 'snapshot_date'], 'net_worth_snapshots_user_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('net_worth_snapshots', function (Blueprint $table) {
            $table->dropUnique('net_worth_snapshots_user_date_unique');
        });
    }
};