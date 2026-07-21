<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('categories')
            ->where('system_key', 'TRANSFER')
            ->update(['keyword' => 'transfer, pindah uang, pindahkan uang, pindahkan saldo, kirim saldo, kirim uang, pindah semua saldo, transfer semua saldo, mutasi, pindah saldo']);

        DB::table('categories')
            ->where('system_key', 'RECEIVABLE')
            ->update(['keyword' => 'ngasih piutang, minjemin, ngutangin, dipinjem, dipinjam, nalangin, kasih utang, pinjemin, pinjamin, ngasih pinjaman, kasih pinjam, meminjamkan, memberi pinjaman']);

        DB::table('categories')
            ->where('system_key', 'RECEIVABLE_PAYMENT')
            ->update(['keyword' => 'terima bayar piutang, dibayar, utang dibayar, utang lunas, ditagih, nagih utang, teman balikin, uang kembali, pelunasan teman, piutang dibayar, dibayar hutang, dibayar utang, balikin uang, balikin pinjaman, mengembalikan pinjaman, mengembalikan uang, kembalikan uang, kupinjamkan, menerima pembayaran hutang, menerima pembayaran piutang']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
