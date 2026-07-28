<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure User exists
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        // 2. Ensure Types exist
        $this->call(TransactionTypeSeeder::class);
        $types = TransactionType::all()->pluck('id', 'name');

        // 3. Get or Create Wallets
        $createdWallets = Wallet::where('user_id', $user->id)->get()->keyBy('name')->all();
        // Wallets are now seeded by WalletSeeder

        // 4. Get or Create Categories
        $categories = [
            // Income (10)
            ['name' => 'Gaji', 'type' => 'Income', 'icon' => 'banknote'],
            ['name' => 'Bonus', 'type' => 'Income', 'icon' => 'gift'],
            ['name' => 'Dividen', 'type' => 'Income', 'icon' => 'trending-up'],
            ['name' => 'Freelance', 'type' => 'Income', 'icon' => 'laptop'],
            ['name' => 'Hadiah', 'type' => 'Income', 'icon' => 'gift'],
            ['name' => 'Jual Barang', 'type' => 'Income', 'icon' => 'package'],
            ['name' => 'Bunga Bank', 'type' => 'Income', 'icon' => 'landmark'],
            ['name' => 'Hasil Investasi', 'type' => 'Income', 'icon' => 'sparkles'],
            ['name' => 'Uang Saku', 'type' => 'Income', 'icon' => 'wallet'],
            ['name' => 'Pendapatan Lain', 'type' => 'Income', 'icon' => 'circle-dollar-sign'],

            // Expense (40)
            ['name' => 'Makan & Minum', 'type' => 'Expense', 'icon' => 'utensils'],
            ['name' => 'Transportasi', 'type' => 'Expense', 'icon' => 'car'],
            ['name' => 'Belanja', 'type' => 'Expense', 'icon' => 'shopping-bag'],
            ['name' => 'Hiburan', 'type' => 'Expense', 'icon' => 'gamepad-2'],
            ['name' => 'Kesehatan', 'type' => 'Expense', 'icon' => 'pill'],
            ['name' => 'Listrik & Air', 'type' => 'Expense', 'icon' => 'lightbulb'],
            ['name' => 'Internet', 'type' => 'Expense', 'icon' => 'globe'],
            ['name' => 'Bensin', 'type' => 'Expense', 'icon' => 'fuel'],
            ['name' => 'Edukasi', 'type' => 'Expense', 'icon' => 'book-open'],
            ['name' => 'Donasi', 'type' => 'Expense', 'icon' => 'handshake'],
            ['name' => 'Pajak', 'type' => 'Expense', 'icon' => 'landmark'],
            ['name' => 'Asuransi', 'type' => 'Expense', 'icon' => 'shield'],
            ['name' => 'Kosmetik', 'type' => 'Expense', 'icon' => 'spray-can'],
            ['name' => 'Olahraga', 'type' => 'Expense', 'icon' => 'dumbbell'],
            ['name' => 'Hewan Peliharaan', 'type' => 'Expense', 'icon' => 'dog'],
            ['name' => 'Perawatan Rumah', 'type' => 'Expense', 'icon' => 'wrench'],
            ['name' => 'Parkir & Tol', 'type' => 'Expense', 'icon' => 'parking-circle'],
            ['name' => 'Nongkrong', 'type' => 'Expense', 'icon' => 'coffee'],
            ['name' => 'Liburan', 'type' => 'Expense', 'icon' => 'umbrella'],
            ['name' => 'Lain-lain', 'type' => 'Expense', 'icon' => 'package'],
            ['name' => 'Cuci Kendaraan', 'type' => 'Expense', 'icon' => 'spray-can'],
            ['name' => 'Pakaian', 'type' => 'Expense', 'icon' => 'shirt'],
            ['name' => 'Sepatu', 'type' => 'Expense', 'icon' => 'footprints'],
            ['name' => 'Subscription', 'type' => 'Expense', 'icon' => 'tv'],
            ['name' => 'Obat-obatan', 'type' => 'Expense', 'icon' => 'stethoscope'],
            ['name' => 'Perawatan Gigi', 'type' => 'Expense', 'icon' => 'tooth'],
            ['name' => 'Potong Rambut', 'type' => 'Expense', 'icon' => 'scissors'],
            ['name' => 'Service Kendaraan', 'type' => 'Expense', 'icon' => 'wrench'],
            ['name' => 'Uang Kas', 'type' => 'Expense', 'icon' => 'banknote'],
            ['name' => 'Kondangan', 'type' => 'Expense', 'icon' => 'heart'],
            ['name' => 'Popok & Susu', 'type' => 'Expense', 'icon' => 'baby'],
            ['name' => 'Mainan Anak', 'type' => 'Expense', 'icon' => 'toy-brick'],
            ['name' => 'Jajan Pasar', 'type' => 'Expense', 'icon' => 'cake'],
            ['name' => 'Pulsa & Data', 'type' => 'Expense', 'icon' => 'smartphone'],
            ['name' => 'Keamanan/Sampah', 'type' => 'Expense', 'icon' => 'trash-2'],
            ['name' => 'Tiket Nonton', 'type' => 'Expense', 'icon' => 'ticket'],
            ['name' => 'Buku & Majalah', 'type' => 'Expense', 'icon' => 'book'],
            ['name' => 'Cicilan Gadget', 'type' => 'Expense', 'icon' => 'smartphone'],
            ['name' => 'Perawatan Wajah', 'type' => 'Expense', 'icon' => 'sparkles'],
            ['name' => 'Bayar Kost/Sewa', 'type' => 'Expense', 'icon' => 'building-2'],

            // Transfer (1)
            ['name' => 'Transfer Saldo', 'type' => 'Transfer', 'icon' => 'arrow-left-right', 'system_key' => 'TRANSFER'],

            // Debt (2)
            ['name' => 'Dapat Hutangan', 'type' => 'Debt', 'icon' => 'hand-coins', 'system_key' => 'LOAN'],
            ['name' => 'Bayar Cicilan Hutang', 'type' => 'Debt', 'icon' => 'circle-dollar-sign', 'system_key' => 'DEBT_PAYMENT'],

            // Receivable (2)
            ['name' => 'Ngasih Piutang', 'type' => 'Receivable', 'icon' => 'file-text', 'system_key' => 'RECEIVABLE'],
            ['name' => 'Terima Bayar Piutang', 'type' => 'Receivable', 'icon' => 'banknote', 'system_key' => 'RECEIVABLE_PAYMENT'],
        ];

        $createdCategories = Category::where('user_id', $user->id)->get()->keyBy('category_name')->all();
        if (empty($createdCategories)) {
            foreach ($categories as $c) {
                $createdCategories[$c['name']] = Category::create([
                    'user_id' => $user->id,
                    'category_name' => $c['name'],
                    'type_id' => $types[$c['type']],
                    'icon' => $c['icon'],
                    'system_key' => $c['system_key'] ?? null,
                    'is_active' => true,
                ]);
            }
        }

        // Liquid wallets pool for easy picking
        $liquidWallets = [
            $createdWallets['Dompet Utama'],
            $createdWallets['BCA'],
            $createdWallets['Mandiri'],
        ];

        // Delete existing transactions for the user to start fresh
        TransactionLog::where('user_id', $user->id)->delete();

        // 5. Create Transaction Logs (Full 2 months of transactions)
        $now = Carbon::now();
        // Go exactly 2 full months ago
        $startOfPeriod = $now->copy()->subMonth()->startOfMonth();
        $daysPassed = $startOfPeriod->diffInDays($now) + 1;

        $transactionsToInsert = [];
        // Generate 400 transactions to thoroughly populate the 2 month span
        for ($i = 0; $i < 400; $i++) {
            $date = $startOfPeriod->copy()->addDays(rand(0, $daysPassed - 1));

            // Weighted randomization
            $rand = rand(1, 100);
            if ($rand <= 60) {
                $typeName = 'Expense';
            } elseif ($rand <= 80) {
                $typeName = 'Income';
            } elseif ($rand <= 85) {
                $typeName = 'Transfer';
            } elseif ($rand <= 92) {
                $typeName = 'Debt';
            } else {
                $typeName = 'Receivable';
            }

            $amount = rand(10000, 500000);
            if (rand(1, 10) > 8) {
                $amount = rand(1000000, 5000000);
            }

            $cat = null;
            $source = null;
            $dest = null;

            if ($typeName === 'Income') {
                $incomeCats = array_values(array_filter($categories, fn ($c) => $c['type'] === 'Income'));
                $catName = $incomeCats[array_rand($incomeCats)]['name'];
                $cat = $createdCategories[$catName];
                $source = $createdWallets['External'];
                $dest = $liquidWallets[rand(0, count($liquidWallets) - 1)];
            } elseif ($typeName === 'Expense') {
                $expenseCats = array_values(array_filter($categories, fn ($c) => $c['type'] === 'Expense'));
                $catName = $expenseCats[array_rand($expenseCats)]['name'];
                $cat = $createdCategories[$catName];
                $source = $liquidWallets[rand(0, count($liquidWallets) - 1)];
                $dest = $createdWallets['Merchant'];
            } elseif ($typeName === 'Transfer') {
                $cat = $createdCategories['Transfer Saldo'];
                $source = $liquidWallets[rand(0, count($liquidWallets) - 1)];
                do {
                    $dest = $liquidWallets[rand(0, count($liquidWallets) - 1)];
                } while ($dest->id === $source->id);
            } elseif ($typeName === 'Debt') {
                $isGettingDebt = rand(0, 1) === 1;
                if ($isGettingDebt) {
                    $cat = $createdCategories['Dapat Hutangan'];
                    $source = $createdWallets['Hutang System'];
                    $dest = $liquidWallets[rand(0, count($liquidWallets) - 1)];
                } else {
                    $cat = $createdCategories['Bayar Cicilan Hutang'];
                    $source = $liquidWallets[rand(0, count($liquidWallets) - 1)];
                    $dest = $createdWallets['Hutang System'];
                }
            } elseif ($typeName === 'Receivable') {
                $isGivingReceivable = rand(0, 1) === 1;
                if ($isGivingReceivable) {
                    $cat = $createdCategories['Ngasih Piutang'];
                    $source = $liquidWallets[rand(0, count($liquidWallets) - 1)];
                    $dest = $createdWallets['Piutang System'];
                } else {
                    $cat = $createdCategories['Terima Bayar Piutang'];
                    $source = $createdWallets['Piutang System'];
                    $dest = $liquidWallets[rand(0, count($liquidWallets) - 1)];
                }
            }

            $transactionsToInsert[] = [
                'reference_number' => 'TRX-'.strtoupper(Str::random(10)),
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'type_id' => $types[$typeName],
                'category_id' => $cat->id,
                'source_wallet_id' => $source->id,
                'destination_wallet_id' => $dest->id,
                'amount' => $amount,
                'balance_before' => 0,
                'balance_after' => 0,
                'subject' => in_array($typeName, ['Debt', 'Receivable']) ? 'Teman '.rand(1, 5) : '-',
                'notes' => 'Testing '.$cat->category_name.' ('.($i + 1).')',
                'is_cleared' => true,
                'created_at' => $date->copy()->addHours(rand(8, 20))->addMinutes(rand(0, 59)),
                'updated_at' => Carbon::now(),
            ];
        }

        // Insert in batches
        foreach (array_chunk($transactionsToInsert, 50) as $chunk) {
            TransactionLog::insert($chunk);
        }
    }
}
