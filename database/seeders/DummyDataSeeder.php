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
        if (empty($createdWallets)) {
            $wallets = [
                ['name' => 'Dompet Utama', 'balance' => 5000000, 'group_type' => 'Liquid', 'icon' => '💰'],
                ['name' => 'BCA', 'balance' => 10000000, 'group_type' => 'Liquid', 'icon' => '💳'],
                ['name' => 'Mandiri', 'balance' => 2500000, 'group_type' => 'Liquid', 'icon' => '💳'],
                ['name' => 'Tabungan Rumah', 'balance' => 50000000, 'group_type' => 'Asset', 'icon' => '🏠'],
                ['name' => 'Investasi Saham', 'balance' => 15000000, 'group_type' => 'Asset', 'icon' => '📈'],
                ['name' => 'Merchant', 'balance' => 0, 'group_type' => 'System', 'icon' => '🏪'],
                ['name' => 'External', 'balance' => 0, 'group_type' => 'System', 'icon' => '🌍'],
                ['name' => 'Hutang System', 'balance' => 0, 'group_type' => 'System', 'icon' => '🤝'],
                ['name' => 'Piutang System', 'balance' => 0, 'group_type' => 'System', 'icon' => '📄'],
            ];

            foreach ($wallets as $w) {
                $createdWallets[$w['name']] = Wallet::create(array_merge($w, ['user_id' => $user->id]));
            }
        }

        // 4. Get or Create Categories
        $categories = [
            // Income (10)
            ['name' => 'Gaji', 'type' => 'Income', 'icon' => '💵'],
            ['name' => 'Bonus', 'type' => 'Income', 'icon' => '🧧'],
            ['name' => 'Dividen', 'type' => 'Income', 'icon' => '📈'],
            ['name' => 'Freelance', 'type' => 'Income', 'icon' => '💻'],
            ['name' => 'Hadiah', 'type' => 'Income', 'icon' => '🎁'],
            ['name' => 'Jual Barang', 'type' => 'Income', 'icon' => '📦'],
            ['name' => 'Bunga Bank', 'type' => 'Income', 'icon' => '🏦'],
            ['name' => 'Hasil Investasi', 'type' => 'Income', 'icon' => '✨'],
            ['name' => 'Uang Saku', 'type' => 'Income', 'icon' => '👛'],
            ['name' => 'Pendapatan Lain', 'type' => 'Income', 'icon' => '💰'],

            // Expense (40)
            ['name' => 'Makan & Minum', 'type' => 'Expense', 'icon' => '🍔'],
            ['name' => 'Transportasi', 'type' => 'Expense', 'icon' => '🚗'],
            ['name' => 'Belanja', 'type' => 'Expense', 'icon' => '🛍️'],
            ['name' => 'Hiburan', 'type' => 'Expense', 'icon' => '🎮'],
            ['name' => 'Kesehatan', 'type' => 'Expense', 'icon' => '💊'],
            ['name' => 'Listrik & Air', 'type' => 'Expense', 'icon' => '💡'],
            ['name' => 'Internet', 'type' => 'Expense', 'icon' => '🌐'],
            ['name' => 'Bensin', 'type' => 'Expense', 'icon' => '⛽'],
            ['name' => 'Edukasi', 'type' => 'Expense', 'icon' => '📚'],
            ['name' => 'Donasi', 'type' => 'Expense', 'icon' => '🤲'],
            ['name' => 'Pajak', 'type' => 'Expense', 'icon' => '🏛️'],
            ['name' => 'Asuransi', 'type' => 'Expense', 'icon' => '🛡️'],
            ['name' => 'Kosmetik', 'type' => 'Expense', 'icon' => '💄'],
            ['name' => 'Olahraga', 'type' => 'Expense', 'icon' => '🏃'],
            ['name' => 'Hewan Peliharaan', 'type' => 'Expense', 'icon' => '🐶'],
            ['name' => 'Perawatan Rumah', 'type' => 'Expense', 'icon' => '🛠️'],
            ['name' => 'Parkir & Tol', 'type' => 'Expense', 'icon' => '🅿️'],
            ['name' => 'Nongkrong', 'type' => 'Expense', 'icon' => '☕'],
            ['name' => 'Liburan', 'type' => 'Expense', 'icon' => '🏖️'],
            ['name' => 'Lain-lain', 'type' => 'Expense', 'icon' => '📦'],
            ['name' => 'Cuci Kendaraan', 'type' => 'Expense', 'icon' => '🧽'],
            ['name' => 'Pakaian', 'type' => 'Expense', 'icon' => '👕'],
            ['name' => 'Sepatu', 'type' => 'Expense', 'icon' => '👟'],
            ['name' => 'Subscription', 'type' => 'Expense', 'icon' => '📺'],
            ['name' => 'Obat-obatan', 'type' => 'Expense', 'icon' => '🩺'],
            ['name' => 'Perawatan Gigi', 'type' => 'Expense', 'icon' => '🦷'],
            ['name' => 'Potong Rambut', 'type' => 'Expense', 'icon' => '✂️'],
            ['name' => 'Service Kendaraan', 'type' => 'Expense', 'icon' => '🔧'],
            ['name' => 'Uang Kas', 'type' => 'Expense', 'icon' => '💵'],
            ['name' => 'Kondangan', 'type' => 'Expense', 'icon' => '💌'],
            ['name' => 'Popok & Susu', 'type' => 'Expense', 'icon' => '🍼'],
            ['name' => 'Mainan Anak', 'type' => 'Expense', 'icon' => '🧸'],
            ['name' => 'Jajan Pasar', 'type' => 'Expense', 'icon' => '🍡'],
            ['name' => 'Pulsa & Data', 'type' => 'Expense', 'icon' => '📱'],
            ['name' => 'Keamanan/Sampah', 'type' => 'Expense', 'icon' => '🗑️'],
            ['name' => 'Tiket Nonton', 'type' => 'Expense', 'icon' => '🎟️'],
            ['name' => 'Buku & Majalah', 'type' => 'Expense', 'icon' => '📖'],
            ['name' => 'Cicilan Gadget', 'type' => 'Expense', 'icon' => '📱'],
            ['name' => 'Perawatan Wajah', 'type' => 'Expense', 'icon' => '🧖‍♀️'],
            ['name' => 'Bayar Kost/Sewa', 'type' => 'Expense', 'icon' => '🏢'],

            // Transfer (1)
            ['name' => 'Transfer Saldo', 'type' => 'Transfer', 'icon' => '🔄', 'system_key' => 'TRANSFER'],

            // Debt (2)
            ['name' => 'Dapat Hutangan', 'type' => 'Debt', 'icon' => '🤝', 'system_key' => 'LOAN'],
            ['name' => 'Bayar Cicilan Hutang', 'type' => 'Debt', 'icon' => '💸', 'system_key' => 'DEBT_PAYMENT'],

            // Receivable (2)
            ['name' => 'Ngasih Piutang', 'type' => 'Receivable', 'icon' => '📄', 'system_key' => 'RECEIVABLE'],
            ['name' => 'Terima Bayar Piutang', 'type' => 'Receivable', 'icon' => '💰', 'system_key' => 'RECEIVABLE_PAYMENT'],
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
