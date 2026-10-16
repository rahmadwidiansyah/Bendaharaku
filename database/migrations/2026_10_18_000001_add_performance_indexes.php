<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indeks baca untuk filter yang terbukti panas di baseline Tahap 2:
     * - transaction_logs(user_id, type_id, date): ringkasan budget per item
     *   dan agregat analytics (sebelumnya hanya terbantu [user_id,date]).
     * - wallets(user_id, group_type): dropdown + fallback popularitas.
     * - categories(user_id, system_key) dan (user_id, type_id): resolusi
     *   kategori sistem dan pemisah income/expense.
     * - GIN trigram untuk pencarian LIKE '%..%': subject/notes transaksi dan
     *   nama kategori (pola LOWER()/ILIKE tak terjangkau indeks btree).
     *
     * Catatan produksi: CREATE INDEX memblokir tulis sesaat pada tabel besar.
     * Untuk tabel transaction_logs jutaan baris, jalankan varian
     * CREATE INDEX CONCURRENTLY manual di luar migrasi pada jam sepi.
     */
    public function up(): void
    {
        Schema::table('transaction_logs', function (Blueprint $table): void {
            $table->index(['user_id', 'type_id', 'date'], 'transaction_logs_user_type_date_index');
        });

        Schema::table('wallets', function (Blueprint $table): void {
            $table->index(['user_id', 'group_type'], 'wallets_user_group_index');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->index(['user_id', 'system_key'], 'categories_user_system_key_index');
            $table->index(['user_id', 'type_id'], 'categories_user_type_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX IF NOT EXISTS transaction_logs_subject_trgm_index ON transaction_logs USING gin (subject gin_trgm_ops)');
            DB::statement('CREATE INDEX IF NOT EXISTS transaction_logs_notes_trgm_index ON transaction_logs USING gin (notes gin_trgm_ops)');
            DB::statement('CREATE INDEX IF NOT EXISTS categories_name_trgm_index ON categories USING gin (category_name gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS transaction_logs_subject_trgm_index');
            DB::statement('DROP INDEX IF EXISTS transaction_logs_notes_trgm_index');
            DB::statement('DROP INDEX IF EXISTS categories_name_trgm_index');
        }

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex('categories_user_type_index');
            $table->dropIndex('categories_user_system_key_index');
        });

        Schema::table('wallets', function (Blueprint $table): void {
            $table->dropIndex('wallets_user_group_index');
        });

        Schema::table('transaction_logs', function (Blueprint $table): void {
            $table->dropIndex('transaction_logs_user_type_date_index');
        });
    }
};
