<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\UserAiMemory;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TransactionTypeSeeder::class);

        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'locale' => 'id',
                'timezone' => 'Asia/Jakarta',
                'bot_name' => 'Ken-Chan',
            ]
        );

        $types = TransactionType::all()->pluck('id', 'name');

        $this->ensureWallets($user, $types);
        $this->ensureCategories($user, $types);
        $this->ensureAiMemories($user);
        $this->ensureConversation($user);
        $this->ensureHistoricalTransactions($user, $types);
    }

    private function ensureWallets(User $user, \Illuminate\Support\Collection $types): void
    {
        Config::set('bendaharaku.system_wallets', [
            'debt' => 'System Hutang',
            'receivable' => 'System Piutang',
            'external' => 'External System',
            'merchant' => 'Merchant System',
        ]);

        $systemWallets = [
            ['name' => 'System Hutang',    'group_type' => 'System', 'keyword' => 'sistem hutang'],
            ['name' => 'System Piutang',   'group_type' => 'System', 'keyword' => 'sistem piutang'],
            ['name' => 'External System',  'group_type' => 'System', 'keyword' => 'external'],
            ['name' => 'Merchant System',  'group_type' => 'System', 'keyword' => 'merchant'],
        ];

        $userWallets = [
            ['name' => 'Cash',  'group_type' => 'Liquid', 'balance' => 3000000,  'is_pinned' => true,  'keyword' => 'cash, tunai, dompet'],
            ['name' => 'BCA',   'group_type' => 'Liquid', 'balance' => 15000000, 'is_pinned' => true,  'keyword' => 'bca'],
            ['name' => 'BRI',   'group_type' => 'Liquid', 'balance' => 5000000,  'is_pinned' => false, 'keyword' => 'bri'],
            ['name' => 'Mandiri', 'group_type' => 'Liquid', 'balance' => 8000000,  'is_pinned' => false, 'keyword' => 'mandiri'],
            ['name' => 'Dana',  'group_type' => 'Liquid', 'balance' => 2000000,  'is_pinned' => true,  'keyword' => 'dana, e-wallet'],
            ['name' => 'OVO',   'group_type' => 'Liquid', 'balance' => 1000000,  'is_pinned' => false, 'keyword' => 'ovo'],
            ['name' => 'GoPay', 'group_type' => 'Liquid', 'balance' => 500000,   'is_pinned' => false, 'keyword' => 'gopay, go pay'],
        ];

        $existingWallets = Wallet::where('user_id', $user->id)->get()->keyBy('name');

        foreach ($systemWallets as $w) {
            if (! $existingWallets->has($w['name'])) {
                Wallet::create(array_merge($w, ['user_id' => $user->id, 'icon' => '🏦', 'balance' => 0, 'is_active' => true]));
            }
        }

        foreach ($userWallets as $w) {
            if (! $existingWallets->has($w['name'])) {
                Wallet::create(array_merge($w, ['user_id' => $user->id, 'icon' => '💳', 'is_active' => true]));
            }
        }
    }

    private function ensureCategories(User $user, \Illuminate\Support\Collection $types): void
    {
        $expenseTypeId = $types['Expense'];
        $incomeTypeId = $types['Income'];
        $transferTypeId = $types['Transfer'];
        $debtTypeId = $types['Debt'];
        $receivableTypeId = $types['Receivable'];

        $categories = [
            ['category_name' => 'Makan & Minum',        'type_id' => $expenseTypeId,    'icon' => '🍔', 'keyword' => 'makan & minum, makan, beli makan, kuliner, restoran, warung, food, minum, gofood, kfc, warkop, warteg, resto, kantin, bekal'],
            ['category_name' => 'Transportasi',         'type_id' => $expenseTypeId,    'icon' => '🚗', 'keyword' => 'transportasi, bensin, gojek, grab, parkir, tol, naik, kendaraan, ojek, taksi'],
            ['category_name' => 'Belanja',              'type_id' => $expenseTypeId,    'icon' => '🛍️', 'keyword' => 'belanja, shopping, grosir, pasar, supermarket, mall, tokped, shopee, lazada, olshop'],
            ['category_name' => 'Hiburan',              'type_id' => $expenseTypeId,    'icon' => '🎮', 'keyword' => 'hiburan, game, nonton, streaming, netflix, spotify, bioskop, konser, liburan'],
            ['category_name' => 'Kesehatan',            'type_id' => $expenseTypeId,    'icon' => '💊', 'keyword' => 'kesehatan, obat, dokter, klinik, rumah sakit, berobat, periksa, lab, vaksin'],
            ['category_name' => 'Listrik & Air',        'type_id' => $expenseTypeId,    'icon' => '💡', 'keyword' => 'listrik, air, pln, pdam, tagihan, utilitas'],
            ['category_name' => 'Pulsa & Internet',     'type_id' => $expenseTypeId,    'icon' => '📱', 'keyword' => 'pulsa, internet, kuota, paket data, telkomsel, indosat, xl, axis, indihome, wifi'],
            ['category_name' => 'Pendidikan',           'type_id' => $expenseTypeId,    'icon' => '📚', 'keyword' => 'pendidikan, kuliah, sekolah, kursus, buku, spp, ukt, seminar, workshop'],
            ['category_name' => 'Pakaian',              'type_id' => $expenseTypeId,    'icon' => '👕', 'keyword' => 'pakaian, baju, celana, sepatu, aksesoris, fashion, pakaian'],
            ['category_name' => 'Donasi',               'type_id' => $expenseTypeId,    'icon' => '🤲', 'keyword' => 'donasi, sedekah, zakat, infaq, amal, sumbangan, kotak amal'],
            ['category_name' => 'Gaji',                 'type_id' => $incomeTypeId,     'icon' => '💵', 'keyword' => 'gaji, salary, penghasilan, pendapatan, upah, honor, thr'],
            ['category_name' => 'Bonus',                'type_id' => $incomeTypeId,     'icon' => '🧧', 'keyword' => 'bonus, insentif, komisi, fee'],
            ['category_name' => 'Freelance',            'type_id' => $incomeTypeId,     'icon' => '💻', 'keyword' => 'freelance, proyek, project, kerja lepas'],
            ['category_name' => 'Pendapatan Lain',      'type_id' => $incomeTypeId,     'icon' => '💰', 'keyword' => 'pendapatan lain, pemasukan lain, uang masuk'],
            ['category_name' => 'Transfer Saldo',       'type_id' => $transferTypeId,   'icon' => '🔄', 'keyword' => 'transfer, pindah saldo, pindah uang, mutasi', 'system_key' => 'TRANSFER'],
            ['category_name' => 'Dapat Hutangan',       'type_id' => $debtTypeId,       'icon' => '📥', 'keyword' => 'hutang, utang, pinjam, hutangan, ngutang', 'system_key' => 'LOAN'],
            ['category_name' => 'Bayar Cicilan Hutang',  'type_id' => $debtTypeId,      'icon' => '💸', 'keyword' => 'bayar hutang, bayar utang, cicilan, lunasin', 'system_key' => 'DEBT_PAYMENT'],
            ['category_name' => 'Ngasih Piutang',        'type_id' => $receivableTypeId, 'icon' => '📤', 'keyword' => 'piutang, minjemin, ngutangin', 'system_key' => 'RECEIVABLE'],
            ['category_name' => 'Terima Bayar Piutang',  'type_id' => $receivableTypeId, 'icon' => '🤑', 'keyword' => 'terima piutang, dibayar, ditagih', 'system_key' => 'RECEIVABLE_PAYMENT'],
        ];

        $existingCats = Category::where('user_id', $user->id)->get()->keyBy('category_name');

        foreach ($categories as $c) {
            $cat = $existingCats->get($c['category_name']);
            if ($cat) {
                $cat->update([
                    'keyword' => $c['keyword'],
                    'icon' => $c['icon'],
                ]);
            } else {
                Category::create(array_merge($c, [
                    'user_id' => $user->id,
                    'is_active' => true,
                ]));
            }
        }
    }

    private function ensureAiMemories(User $user): void
    {
        $wallets = Wallet::where('user_id', $user->id)->get()->keyBy('name');
        $cats = Category::where('user_id', $user->id)->get()->keyBy('category_name');

        $memories = [
            [
                'keyword_pattern' => 'makan|beli|kuliner|restoran|warung|food',
                'raw_subject' => 'makan',
                'normalized_subject' => 'makan',
                'memory_keyword' => 'makan',
                'category_id' => $cats->get('Makan & Minum')?->id,
                'wallet_id' => $wallets->get('BCA')?->id,
                'hit_count' => 15,
                'weight' => 0.95,
                'last_applied_at' => now(),
            ],
            [
                'keyword_pattern' => 'gaji|salary|honor|pendapatan',
                'raw_subject' => 'gaji',
                'normalized_subject' => 'gaji',
                'memory_keyword' => 'gaji',
                'category_id' => $cats->get('Gaji')?->id,
                'wallet_id' => $wallets->get('Mandiri')?->id,
                'hit_count' => 8,
                'weight' => 0.9,
                'last_applied_at' => now(),
            ],
            [
                'keyword_pattern' => 'grab|gojek|transport|naik|ojek|taksi',
                'raw_subject' => 'transportasi',
                'normalized_subject' => 'transportasi',
                'memory_keyword' => 'transportasi',
                'category_id' => $cats->get('Transportasi')?->id,
                'wallet_id' => $wallets->get('Dana')?->id,
                'hit_count' => 12,
                'weight' => 0.88,
                'last_applied_at' => now(),
            ],
        ];

        foreach ($memories as $m) {
            UserAiMemory::updateOrCreate(
                ['user_id' => $user->id, 'memory_keyword' => $m['memory_keyword']],
                $m
            );
        }
    }

    private function ensureConversation(User $user): void
    {
        $conversation = Conversation::firstOrCreate(
            ['user_id' => $user->id, 'is_active' => true, 'archived_at' => null],
            ['title' => 'Chat Utama', 'metadata' => ['source' => 'web']]
        );

        $conversation->update(['title' => 'Chat Utama']);
    }

    private function ensureHistoricalTransactions(User $user, \Illuminate\Support\Collection $types): void
    {
        $wallets = Wallet::where('user_id', $user->id)->get()->keyBy('name');
        $cats = Category::where('user_id', $user->id)->get()->keyBy('category_name');

        $externalId = $wallets->get('External System')?->id;
        $merchantId = $wallets->get('Merchant System')?->id;
        $debtId = $wallets->get('System Hutang')?->id;
        $receivableId = $wallets->get('System Piutang')?->id;

        $transactions = [
            [
                'amount' => 15000, 'subject' => 'Beli makan siang',
                'category_id' => $cats->get('Makan & Minum')?->id,
                'source_wallet_id' => $wallets->get('BCA')?->id,
                'destination_wallet_id' => $merchantId,
                'type_id' => $types['Expense'],
                'date' => Carbon::now()->subDays(2),
            ],
            [
                'amount' => 25000, 'subject' => 'Naik Grab ke kantor',
                'category_id' => $cats->get('Transportasi')?->id,
                'source_wallet_id' => $wallets->get('Dana')?->id,
                'destination_wallet_id' => $merchantId,
                'type_id' => $types['Expense'],
                'date' => Carbon::now()->subDays(3),
            ],
            [
                'amount' => 8000000, 'subject' => 'Gaji bulan ini',
                'category_id' => $cats->get('Gaji')?->id,
                'source_wallet_id' => $externalId,
                'destination_wallet_id' => $wallets->get('Mandiri')?->id,
                'type_id' => $types['Income'],
                'date' => Carbon::now()->subDays(5),
            ],
            [
                'amount' => 500000, 'subject' => 'Bayar hutang Budi',
                'category_id' => $cats->get('Bayar Cicilan Hutang')?->id,
                'source_wallet_id' => $wallets->get('Cash')?->id,
                'destination_wallet_id' => $debtId,
                'type_id' => $types['Debt'],
                'date' => Carbon::now()->subDays(7),
            ],
            [
                'amount' => 50000, 'subject' => 'Beli bahan masakan',
                'category_id' => $cats->get('Belanja')?->id,
                'source_wallet_id' => $wallets->get('Cash')?->id,
                'destination_wallet_id' => $merchantId,
                'type_id' => $types['Expense'],
                'date' => Carbon::now()->subDays(1),
            ],
        ];

        foreach ($transactions as $t) {
            TransactionLog::create(array_merge($t, [
                'user_id' => $user->id,
                'reference_number' => 'TRX-TEST-'.strtoupper(Str::random(8)),
                'balance_before' => 0,
                'balance_after' => 0,
                'notes' => $t['subject'],
                'is_cleared' => true,
            ]));
        }
    }
}
