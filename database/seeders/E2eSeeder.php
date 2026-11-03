<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Deterministic fixture for the Playwright suite.
 *
 * Two accounts share the same data so visual/a11y specs can run in the
 * Default and Material UI without touching preferences at runtime.
 *
 * Run with E2E_NOW set (e.g. 2026-10-15 10:00:00) for stable dates.
 */
class E2eSeeder extends Seeder
{
    private const ACCOUNTS = [
        'e2e-default@example.com' => ['name' => 'E2E Default', 'ui_theme' => 'default'],
        'e2e-m3@example.com' => ['name' => 'E2E Material', 'ui_theme' => 'm3'],
    ];

    public function run(): void
    {
        $this->call(TransactionTypeSeeder::class);

        $types = TransactionType::query()->pluck('id', 'name');
        $now = config('app.e2e_now') ? Carbon::parse(config('app.e2e_now')) : Carbon::now();

        foreach (self::ACCOUNTS as $email => $attributes) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $attributes['name'],
                    'password' => bcrypt('password'),
                    'theme' => 'system',
                    'locale' => 'en',
                    'timezone' => 'Asia/Jakarta',
                    'accent_color' => 'teal',
                    'ui_theme' => $attributes['ui_theme'],
                    'ui_m3_color_scheme' => $attributes['ui_theme'] === 'm3' ? 'rose' : 'teal',
                    'category_icon_colored' => true,
                ]
            );

            $user->forceFill(['email_verified_at' => now()])->save();

            $this->seedUserData($user, $types, $now);
        }
    }

    private function seedUserData(User $user, $types, Carbon $now): void
    {
        TransactionLog::where('user_id', $user->id)->forceDelete();
        Category::where('user_id', $user->id)->forceDelete();
        Wallet::where('user_id', $user->id)->forceDelete();

        // Nama dompet sistem DERET SSOT: WalletResolutionService mencari
        // dari config('bendaharaku.system_wallets.*'), bukan nama hardcode.
        $sw = config('bendaharaku.system_wallets');

        $wallets = [
            'BCA' => ['balance' => 10000000, 'group_type' => 'Liquid', 'icon' => 'credit-card'],
            'Cash' => ['balance' => 1500000, 'group_type' => 'Liquid', 'icon' => 'wallet'],
            'Tabungan' => ['balance' => 25000000, 'group_type' => 'Asset', 'icon' => 'home'],
            $sw['merchant'] => ['balance' => 0, 'group_type' => 'System', 'icon' => 'store'],
            $sw['external'] => ['balance' => 0, 'group_type' => 'System', 'icon' => 'globe'],
            $sw['debt'] => ['balance' => 0, 'group_type' => 'System', 'icon' => 'hand-coins'],
            $sw['receivable'] => ['balance' => 0, 'group_type' => 'System', 'icon' => 'receipt'],
        ];

        $walletModels = [];
        foreach ($wallets as $name => $data) {
            $walletModels[$name] = Wallet::create([
                'user_id' => $user->id,
                'name' => $name,
                'balance' => $data['balance'],
                'group_type' => $data['group_type'],
                'icon' => $data['icon'],
                'is_active' => true,
            ]);
        }

        $categories = [
            'Makan' => ['type' => 'Expense', 'icon' => 'utensils', 'system_key' => null,
                'keyword' => 'makan, minum, kopi, warung, bakso, nasi, restoran'],
            'Transport' => ['type' => 'Expense', 'icon' => 'car', 'system_key' => null,
                'keyword' => 'bensin, parkir, ojek, karcis, tol, transport'],
            'Belanja' => ['type' => 'Expense', 'icon' => 'shopping-bag', 'system_key' => null,
                'keyword' => 'belanja, baju, sepatu, minyak, pasar'],
            'Gaji' => ['type' => 'Income', 'icon' => 'banknote', 'system_key' => null,
                'keyword' => 'gaji, salary, upah'],
            'Bonus' => ['type' => 'Income', 'icon' => 'gift', 'system_key' => null,
                'keyword' => 'bonus, keuntungan, komisi'],
            'Transfer Saldo' => ['type' => 'Transfer', 'icon' => 'arrow-left-right', 'system_key' => 'TRANSFER'],
            'Dapat Hutangan' => ['type' => 'Debt', 'icon' => 'hand-coins', 'system_key' => 'LOAN'],
            'Bayar Cicilan Hutang' => ['type' => 'Debt', 'icon' => 'circle-dollar-sign', 'system_key' => 'DEBT_PAYMENT'],
            'Ngasih Piutang' => ['type' => 'Receivable', 'icon' => 'file-text', 'system_key' => 'RECEIVABLE'],
            'Terima Bayar Piutang' => ['type' => 'Receivable', 'icon' => 'banknote', 'system_key' => 'RECEIVABLE_PAYMENT'],
        ];

        $categoryModels = [];
        foreach ($categories as $name => $data) {
            $categoryModels[$name] = Category::create([
                'user_id' => $user->id,
                'type_id' => $types[$data['type']],
                'category_name' => $name,
                'keyword' => $data['keyword'] ?? null,
                'icon' => $data['icon'],
                'system_key' => $data['system_key'],
                'is_active' => true,
            ]);
        }

        $rows = [
            ['Gaji', 'Income', $sw['external'], 'BCA', 8500000, 'Gaji bulanan'],
            ['Bonus', 'Income', $sw['external'], 'BCA', 1250000, 'Bonus proyek'],
            ['Makan', 'Expense', 'BCA', $sw['merchant'], 125000, 'Makan siang'],
            ['Transport', 'Expense', 'Cash', $sw['merchant'], 45000, 'Transport kantor'],
            ['Belanja', 'Expense', 'BCA', $sw['merchant'], 350000, 'Belanja bulanan'],
            ['Transfer Saldo', 'Transfer', 'BCA', 'Cash', 500000, 'Isi uang tunai'],
            ['Dapat Hutangan', 'Debt', $sw['debt'], 'BCA', 1000000, 'Pinjaman teman'],
            ['Ngasih Piutang', 'Receivable', 'BCA', $sw['receivable'], 750000, 'Piutang keluarga'],
        ];

        $reference = 0;

        for ($day = 0; $day < 10; $day++) {
            foreach ($rows as $index => $row) {
                $reference++;
                [$category, $type, $source, $destination, $amount, $notes] = $row;
                $date = $now->copy()->subDays($day)->setTime(8 + ($index % 10), 15, 0);

                TransactionLog::create([
                    'reference_number' => sprintf('E2E-%s-%03d', $user->id, $reference),
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    'type_id' => $types[$type],
                    'category_id' => $categoryModels[$category]->id,
                    'source_wallet_id' => $walletModels[$source]->id,
                    'destination_wallet_id' => $walletModels[$destination]->id,
                    'amount' => $amount + ($day * 5000),
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'subject' => $category,
                    'notes' => $notes,
                    'is_cleared' => true,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }
}
