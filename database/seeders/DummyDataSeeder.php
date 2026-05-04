<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Category;
use App\Models\TransactionType;
use App\Models\TransactionLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

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

        // 2. Ensure Types exist (Calling the existing seeder)
        $this->call(TransactionTypeSeeder::class);
        $types = TransactionType::all()->pluck('id', 'name');

        // 3. Create Wallets
        $wallets = [
            ['name' => 'Dompet Utama', 'balance' => 5000000, 'group_type' => 'Liquid', 'icon' => '💰'],
            ['name' => 'BCA', 'balance' => 10000000, 'group_type' => 'Liquid', 'icon' => 'https://pustaka.bca.co.id/public-assets/logo-bca.svg'],
            ['name' => 'Mandiri', 'balance' => 2500000, 'group_type' => 'Liquid', 'icon' => '💳'],
            ['name' => 'Tabungan Rumah', 'balance' => 50000000, 'group_type' => 'Asset', 'icon' => '🏠'],
            ['name' => 'Investasi Saham', 'balance' => 15000000, 'group_type' => 'Asset', 'icon' => '📈'],
            ['name' => 'Merchant', 'balance' => 0, 'group_type' => 'System', 'icon' => '🏪'],
            ['name' => 'External', 'balance' => 0, 'group_type' => 'System', 'icon' => '🌍'],
            ['name' => 'Hutang System', 'balance' => 0, 'group_type' => 'System', 'icon' => '🤝'],
            ['name' => 'Piutang System', 'balance' => 0, 'group_type' => 'System', 'icon' => '📄'],
        ];

        $createdWallets = [];
        foreach ($wallets as $w) {
            $createdWallets[$w['name']] = Wallet::updateOrCreate(
                ['user_id' => $user->id, 'name' => $w['name']],
                $w
            );
        }

        // 4. Create Categories
        $categories = [
            ['name' => 'Gaji', 'type' => 'Income', 'icon' => '💵'],
            ['name' => 'Bonus', 'type' => 'Income', 'icon' => '🧧'],
            ['name' => 'Dividen', 'type' => 'Income', 'icon' => '📈'],
            ['name' => 'Makan & Minum', 'type' => 'Expense', 'icon' => '🍔'],
            ['name' => 'Transportasi', 'type' => 'Expense', 'icon' => '🚗'],
            ['name' => 'Belanja', 'type' => 'Expense', 'icon' => '🛍️'],
            ['name' => 'Hiburan', 'type' => 'Expense', 'icon' => '🎮'],
            ['name' => 'Kesehatan', 'type' => 'Expense', 'icon' => '💊'],
            ['name' => 'Listrik & Air', 'type' => 'Expense', 'icon' => '💡'],
            ['name' => 'Transfer Saldo', 'type' => 'Transfer', 'icon' => '🔄'],
            ['name' => 'Dapat Hutangan', 'type' => 'Debt', 'icon' => '🤝'],
            ['name' => 'Ngasih Piutang', 'type' => 'Receivable', 'icon' => '📄'],
        ];

        $createdCategories = [];
        foreach ($categories as $c) {
            $createdCategories[$c['name']] = Category::updateOrCreate(
                ['user_id' => $user->id, 'category_name' => $c['name']],
                [
                    'type_id' => $types[$c['type']],
                    'icon' => $c['icon'],
                    'is_active' => true,
                ]
            );
        }

        // 5. Create Transaction Logs (Random data for the current month)
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        
        for ($i = 0; $i < 100; $i++) {
            $date = $startOfMonth->copy()->addDays(rand(0, $now->day - 1));
            $typeNames = ['Income', 'Expense', 'Transfer'];
            $typeName = $typeNames[array_rand($typeNames)];
            
            $amount = rand(10000, 500000);
            if (rand(1, 10) > 8) $amount = rand(1000000, 5000000); // Occasional big amount

            $cat = null;
            $source = null;
            $dest = null;

            if ($typeName === 'Income') {
                $incomeCats = ['Gaji', 'Bonus', 'Dividen'];
                $cat = $createdCategories[$incomeCats[array_rand($incomeCats)]];
                $source = $createdWallets['External'];
                $dest = array_values($createdWallets)[rand(0, 2)]; // Main, BCA, or Mandiri
            } elseif ($typeName === 'Expense') {
                $expenseCats = ['Makan & Minum', 'Transportasi', 'Belanja', 'Hiburan', 'Kesehatan', 'Listrik & Air'];
                $cat = $createdCategories[$expenseCats[array_rand($expenseCats)]];
                $source = array_values($createdWallets)[rand(0, 2)];
                $dest = $createdWallets['Merchant'];
            } else {
                $cat = $createdCategories['Transfer Saldo'];
                $walletsPool = [$createdWallets['Dompet Utama'], $createdWallets['BCA'], $createdWallets['Mandiri']];
                $source = $walletsPool[array_rand($walletsPool)];
                do {
                    $dest = $walletsPool[array_rand($walletsPool)];
                } while ($dest->id === $source->id);
            }

            TransactionLog::create([
                'reference_number' => 'TRX-' . strtoupper(Str::random(10)),
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'type_id' => $types[$typeName],
                'category_id' => $cat->id,
                'source_wallet_id' => $source->id,
                'destination_wallet_id' => $dest->id,
                'amount' => $amount,
                'balance_before' => 0,
                'balance_after' => 0,
                'subject' => '-',
                'notes' => 'Testing ' . $cat->category_name . ' (' . ($i + 1) . ')',
                'is_cleared' => true,
                'created_at' => $date->copy()->addHours(rand(8, 20))->addMinutes(rand(0, 59)),
            ]);
        }
    }
}
