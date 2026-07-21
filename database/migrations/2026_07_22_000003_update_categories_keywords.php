<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter keyword column to be text to avoid length limitation issues
        Schema::table('categories', function (Blueprint $table) {
            $table->text('keyword')->nullable()->change();
        });

        DB::table('categories')
            ->where('system_key', 'TRANSFER')
            ->update(['keyword' => 'transfer, pindah uang, pindahkan saldo, kirim saldo, kirim uang, pindah semua saldo, transfer semua saldo, mutasi, pindah saldo']);

        DB::table('categories')
            ->where('system_key', 'LOAN')
            ->update(['keyword' => 'dapat hutangan, ngutang, pinjam duit, ditalangin, kasbon, pinjol, minjem uang, pinjam uang, dapet pinjeman, hutang, utang, pinjam, minjam, pinjem, berhutang, berutang']);

        DB::table('categories')
            ->where('system_key', 'DEBT_PAYMENT')
            ->update(['keyword' => 'bayar utang, bayar hutang, lunasin, nyicil, cicilan, balikin duit, balikin uang, ganti duit, nutup utang, bayar kasbon, bayar pinjol, lunasi hutang, lunasin utang, balikin pinjaman, melunasi pinjaman, kembalikan hutang']);

        DB::table('categories')
            ->where('system_key', 'RECEIVABLE')
            ->update(['keyword' => 'ngasih piutang, minjemin, ngutangin, dipinjem, dipinjam, nalangin, kasih utang, pinjemin, ngasih pinjaman, kasih pinjam, meminjamkan, memberi pinjaman']);

        DB::table('categories')
            ->where('system_key', 'RECEIVABLE_PAYMENT')
            ->update(['keyword' => 'terima bayar piutang, dibayar, utang dibayar, utang lunas, ditagih, nagih utang, teman balikin, uang kembali, pelunasan teman, piutang dibayar, dibayar hutang, dibayar utang, balikin uang, balikin pinjaman, mengembalikan pinjaman, menerima pembayaran hutang, menerima pembayaran piutang']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('keyword', 255)->nullable()->change();
        });
    }
};
