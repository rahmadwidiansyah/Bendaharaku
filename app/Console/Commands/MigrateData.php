<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TransactionLog;
use App\Models\Wallet;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MigrateData extends Command
{
    // Nama command yang akan diketik di terminal
    protected $signature = 'migrate:transactions';
    
    // Deskripsi command
    protected $description = 'Migrasi data transaksi dari JSON lama ke sistem baru Bendaharaku V4';

    public function handle()
    {
        // 1. Baca file JSON dari storage/app/transactions.json
        $filePath = storage_path('app/transactions.json');
        if (!file_exists($filePath)) {
            $this->error("File transactions.json tidak ditemukan di storage/app!");
            return;
        }

        $json = file_get_contents($filePath);
        $transactions = json_decode($json, true);

        if (!$transactions) {
            $this->error("Format JSON tidak valid!");
            return;
        }

        // Tentukan ID User utama (Ubah jika ID kamu bukan 1)
        $userId = 2; 

        // ==========================================
        // 2. AREA KAMUS MAPPING (HARUS KAMU SESUAIKAN)
        // ==========================================
        
        // Mapping Nama Kategori Lama -> ID Kategori Baru
        $categoryMapping = [
            "🔄 Pindah Saldo" => 1,  // Contoh: ID 5 di tabel categories
            "📥 Piutang (Masuk)"          => 5,
            "💸 Hutang (Masuk)"           => 2,
            "🧾 Cicil Hutang (Keluar)"           => 3,
           "💰 Piutang (Keluar)"           => 4,
           "🎓 Pendidikan & Kuliah"           => 8,
           "🏠 Biaya Kost & Sewa"           => 9,
           "🛍️ Belanja & Fashion"           => 13,
           "🚗 Transportasi"           => 7,
           "📱 Data & Digital"           => 17,
           "🍔 Makan & Minum"           => 6,
           "🍟 Jajan & Nongkrong"           => 11,
          "🛒 Groceries & Stok Dapur"           => 10,
          "🛠️ Servis & Barang Hobby"           => 14,
          "💸 Admin & Pajak"           => 15,
          "🎁 Sosial & Sedekah"           => 16,
          "❤️ Transfer Muna"           => 16,
          "💊 Kesehatan"           => 72,
          "🏠 Kebutuhan Rumah & Ikan"           => 73,
          "❓ Lain-lain (Pengeluaran)"           => 74,
          "🛎️ Kiriman/TF Masuk"           => 75,
          "🛵 Side Job/Tambahan"           => 21,
          "❓ Lain-lain (Pemasukan)"           => 23,

            // Tambahkan semua nama kategori dari DB lamamu di sini...
        ];
        $fallbackCategoryId = 1; // ID Kategori "Lainnya/Uncategorized" jika nama tidak ada di kamus

        // Mapping Nama Akun/Dompet Lama -> ID Wallet Baru
        $walletMapping = [
            "CASH"     => 5, // Contoh: ID 1 untuk Dompet Tunai
            "DANA"      => 12,
            "MARKET PULSA" => 11,
            "SHOPEEPAY"           => 13,
           "SEABANK"           => 14,
          "GOPAY"           => 15,
          "TABUNGAN"           => 24,
          "MERCHANT"           => 4,
          "HUTANG"           => 1,
          "EKSTERNAL"          => 3,
          "PIUTANG"          => 2,
          // Contoh: ID 3 untuk Wallet Sistem Pengeluaran
            // Tambahkan nama akun lain jika ada...
        ];
        // ==========================================

        $this->info('Memulai proses migrasi ' . count($transactions) . ' data...');
        $this->output->progressStart(count($transactions));
        $errorCount = 0;

        foreach ($transactions as $row) {
            try {
                // Bungkus dalam DB::transaction agar saldo aman
                DB::transaction(function () use ($row, $userId, $categoryMapping, $fallbackCategoryId, $walletMapping) {
                    
                    // --- A. TERJEMAHKAN STRING LAMA KE ID BARU ---
                    $oldCategoryName = $row['kategori'] ?? 'Unknown';
                    $newCategoryId = $categoryMapping[$oldCategoryName] ?? $fallbackCategoryId;
                    $category = Category::findOrFail($newCategoryId);

                    $oldSourceName = $row['source_account'];
                    $newSourceId = $walletMapping[$oldSourceName] ?? null;
                    
                    $oldDestName = $row['destination_account'];
                    $newDestId = $walletMapping[$oldDestName] ?? null;

                    if (!$newSourceId || !$newDestId) {
                        throw new \Exception("Wallet '{$oldSourceName}' atau '{$oldDestName}' tidak ada di kamus mapping!");
                    }

                    $source = Wallet::findOrFail($newSourceId);
                    $destination = Wallet::findOrFail($newDestId);


                    // --- B. PROSES MATEMATIKA SALDO ---
                    // Tentukan mana wallet utama (bukan system) untuk mencatat balance_before & after
                    $mainWallet = ($source->group_type !== 'System') ? $source : $destination;
                    $balanceBefore = $mainWallet->balance;

                    $amount = (float) $row['nominal'];
                    
                    // Kurangi sumber, Tambah tujuan
                    $source->balance -= $amount;
                    $destination->balance += $amount;

                    $source->save();
                    $destination->save();

                    $balanceAfter = $mainWallet->balance;


                    // --- C. BERSIHKAN DATA LAINNYA ---
                    $subject = ($row['subject'] === "null" || empty($row['subject'])) ? '-' : $row['subject'];
                    $date = Carbon::parse($row['tanggal'])->format('Y-m-d');


                    // --- D. INSERT KE TRANSACTION LOGS ---
                    TransactionLog::create([
                        'reference_number'      => 'TRX-MIG-' . strtoupper(Str::random(8)),
                        'user_id'               => $userId,
                        'date'                  => $date,
                        'type_id'               => $category->type_id, // Ambil type_id dari relasi category
                        'category_id'           => $category->id,
                        'source_wallet_id'      => $source->id,
                        'destination_wallet_id' => $destination->id,
                        'amount'                => $amount,
                        'balance_before'        => $balanceBefore,
                        'balance_after'         => $balanceAfter,
                        'subject'               => $subject,
                        'notes'                 => $row['catatan'] ?? null,
                        'is_cleared'            => true,
                        // created_at mengikuti waktu script dijalankan, sesuai default Laravel
                    ]);
                });

            } catch (\Exception $e) {
                // Jika 1 baris gagal, script tidak mati, tapi dicatat errornya
                $this->error("\nGagal memproses data ID lama {$row['id']}: " . $e->getMessage());
                $errorCount++;
            }

            // Majukan progress bar
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        
        if ($errorCount > 0) {
            $this->warn("Migrasi selesai, namun terdapat {$errorCount} data yang gagal diproses. Cek pesan error di atas.");
        } else {
            $this->info('Migrasi Selesai 100% dengan sukses! Saldo dompet telah diperbarui.');
        }
    }
}