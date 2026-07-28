<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    public static array $iconMap = [
        'Dompet Utama' => 'wallet',
        'Cash' => 'wallet',
        'BCA' => 'credit-card',
        'Mandiri' => 'building-2',
        'BRI' => 'building',
        'Dana' => 'smartphone',
        'OVO' => 'smartphone',
        'GoPay' => 'smartphone',
        'Tabungan Rumah' => 'home',
        'Investasi Saham' => 'trending-up',
        'Merchant' => 'store',
        'Merchant System' => 'store',
        'External' => 'globe',
        'External System' => 'globe',
        'Hutang System' => 'hand-coins',
        'System Hutang' => 'hand-coins',
        'Piutang System' => 'receipt',
        'System Piutang' => 'receipt',
    ];

    public static function iconFor(string $name): string
    {
        return self::$iconMap[$name] ?? 'wallet';
    }

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        $wallets = [
            ['name' => 'Dompet Utama', 'balance' => 5000000, 'group_type' => 'Liquid'],
            ['name' => 'BCA', 'balance' => 10000000, 'group_type' => 'Liquid'],
            ['name' => 'Mandiri', 'balance' => 2500000, 'group_type' => 'Liquid'],
            ['name' => 'Tabungan Rumah', 'balance' => 50000000, 'group_type' => 'Asset'],
            ['name' => 'Investasi Saham', 'balance' => 15000000, 'group_type' => 'Asset'],
            ['name' => 'Merchant', 'balance' => 0, 'group_type' => 'System'],
            ['name' => 'External', 'balance' => 0, 'group_type' => 'System'],
            ['name' => 'Hutang System', 'balance' => 0, 'group_type' => 'System'],
            ['name' => 'Piutang System', 'balance' => 0, 'group_type' => 'System'],
        ];

        foreach ($wallets as $w) {
            Wallet::firstOrCreate(
                ['user_id' => $user->id, 'name' => $w['name']],
                [
                    'balance' => $w['balance'],
                    'group_type' => $w['group_type'],
                    'icon' => self::iconFor($w['name']),
                ]
            );
        }
    }
}
