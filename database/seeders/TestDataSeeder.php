<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BudgetExpenseGroup;
use App\Models\BudgetGroup;
use App\Models\BudgetItem;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\UserAiMemory;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
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
        $this->ensureCurrentMonthBudget($user);
    }

    private function ensureWallets(User $user, Collection $types): void
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
                Wallet::create(array_merge($w, ['user_id' => $user->id, 'icon' => WalletSeeder::iconFor($w['name']), 'balance' => 0, 'is_active' => true]));
            }
        }

        foreach ($userWallets as $w) {
            if (! $existingWallets->has($w['name'])) {
                Wallet::create(array_merge($w, ['user_id' => $user->id, 'icon' => WalletSeeder::iconFor($w['name']), 'is_active' => true]));
            }
        }
    }

    private function ensureCategories(User $user, Collection $types): void
    {
        $expenseTypeId = $types['Expense'];
        $incomeTypeId = $types['Income'];
        $transferTypeId = $types['Transfer'];
        $debtTypeId = $types['Debt'];
        $receivableTypeId = $types['Receivable'];

        $categories = [
            ['category_name' => 'Makan & Minum',        'type_id' => $expenseTypeId,    'icon' => 'utensils', 'keyword' => 'makan & minum, makan, beli makan, kuliner, restoran, warung, food, minum, gofood, kfc, warkop, warteg, resto, kantin, bekal'],
            ['category_name' => 'Transportasi',         'type_id' => $expenseTypeId,    'icon' => 'car', 'keyword' => 'transportasi, bensin, gojek, grab, parkir, tol, naik, kendaraan, ojek, taksi'],
            ['category_name' => 'Belanja',              'type_id' => $expenseTypeId,    'icon' => 'shopping-bag', 'keyword' => 'belanja, shopping, grosir, pasar, supermarket, mall, tokped, shopee, lazada, olshop'],
            ['category_name' => 'Hiburan',              'type_id' => $expenseTypeId,    'icon' => 'gamepad-2', 'keyword' => 'hiburan, game, nonton, streaming, netflix, spotify, bioskop, konser, liburan'],
            ['category_name' => 'Kesehatan',            'type_id' => $expenseTypeId,    'icon' => 'pill', 'keyword' => 'kesehatan, obat, dokter, klinik, rumah sakit, berobat, periksa, lab, vaksin'],
            ['category_name' => 'Listrik & Air',        'type_id' => $expenseTypeId,    'icon' => 'lightbulb', 'keyword' => 'listrik, air, pln, pdam, tagihan, utilitas'],
            ['category_name' => 'Pulsa & Internet',     'type_id' => $expenseTypeId,    'icon' => 'smartphone', 'keyword' => 'pulsa, internet, kuota, paket data, telkomsel, indosat, xl, axis, indihome, wifi'],
            ['category_name' => 'Pendidikan',           'type_id' => $expenseTypeId,    'icon' => 'book-open', 'keyword' => 'pendidikan, kuliah, sekolah, kursus, buku, spp, ukt, seminar, workshop'],
            ['category_name' => 'Pakaian',              'type_id' => $expenseTypeId,    'icon' => 'shirt', 'keyword' => 'pakaian, baju, celana, sepatu, aksesoris, fashion, pakaian'],
            ['category_name' => 'Donasi',               'type_id' => $expenseTypeId,    'icon' => 'handshake', 'keyword' => 'donasi, sedekah, zakat, infaq, amal, sumbangan, kotak amal'],
            ['category_name' => 'Gaji',                 'type_id' => $incomeTypeId,     'icon' => 'banknote', 'keyword' => 'gaji, salary, penghasilan, pendapatan, upah, honor, thr'],
            ['category_name' => 'Bonus',                'type_id' => $incomeTypeId,     'icon' => 'gift', 'keyword' => 'bonus, insentif, komisi, fee'],
            ['category_name' => 'Freelance',            'type_id' => $incomeTypeId,     'icon' => 'laptop', 'keyword' => 'freelance, proyek, project, kerja lepas'],
            ['category_name' => 'Pendapatan Lain',      'type_id' => $incomeTypeId,     'icon' => 'circle-dollar-sign', 'keyword' => 'pendapatan lain, pemasukan lain, uang masuk'],
            ['category_name' => 'Transfer Saldo',       'type_id' => $transferTypeId,   'icon' => 'arrow-left-right', 'keyword' => 'transfer, pindah saldo, pindah uang, mutasi', 'system_key' => 'TRANSFER'],
            ['category_name' => 'Dapat Hutangan',       'type_id' => $debtTypeId,       'icon' => 'hand-coins', 'keyword' => 'hutang, utang, pinjam, hutangan, ngutang', 'system_key' => 'LOAN'],
            ['category_name' => 'Bayar Cicilan Hutang',  'type_id' => $debtTypeId,      'icon' => 'circle-dollar-sign', 'keyword' => 'bayar hutang, bayar utang, cicilan, lunasin', 'system_key' => 'DEBT_PAYMENT'],
            ['category_name' => 'Ngasih Piutang',        'type_id' => $receivableTypeId, 'icon' => 'file-text', 'keyword' => 'piutang, minjemin, ngutangin', 'system_key' => 'RECEIVABLE'],
            ['category_name' => 'Terima Bayar Piutang',  'type_id' => $receivableTypeId, 'icon' => 'banknote', 'keyword' => 'terima piutang, dibayar, ditagih', 'system_key' => 'RECEIVABLE_PAYMENT'],
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

    private function ensureCurrentMonthBudget(User $user): void
    {
        $cats = Category::where('user_id', $user->id)->get()->keyBy('category_name');

        $plan = [
            'Makan & Minum'     => ['amount' => 600000, 'group' => 'variable'],
            'Belanja'           => ['amount' => 650000, 'group' => 'variable'],
            'Transportasi'      => ['amount' => 250000, 'group' => 'variable'],
            'Listrik & Air'     => ['amount' => 165000, 'group' => 'fixed'],
            'Pulsa & Internet'  => ['amount' => 120000, 'group' => 'fixed'],
            'Pendidikan'        => ['amount' => 500000, 'group' => 'fixed'],
            'Hiburan'           => ['amount' => 165000, 'group' => 'discretionary'],
            'Donasi'            => ['amount' => 50000,  'group' => 'discretionary'],
            'Kesehatan'         => ['amount' => 100000, 'group' => 'sinking_fund'],
            'Pakaian'           => ['amount' => 150000, 'group' => 'sinking_fund'],
        ];

        $now = Carbon::now();
        $budget = BudgetGroup::updateOrCreate(
            [
                'user_id' => $user->id,
                'period_month' => $now->month,
                'period_year' => $now->year,
            ],
            [
                'total_budget_amount' => array_sum(array_column($plan, 'amount')),
                'ai_notes' => 'Anggaran bulan ini disusun berdasarkan rata-rata pengeluaran 3 bulan terakhir. Prioritaskan kebutuhan tetap dan sisihkan untuk tabungan.',
                'generated_by' => 'manual',
            ]
        );

        $budget->items()->delete();
        $budget->expenseGroups()->delete();

        foreach ($plan as $categoryName => $p) {
            $category = $cats->get($categoryName);
            if (! $category) {
                continue;
            }
            BudgetItem::create([
                'budget_group_id' => $budget->id,
                'budgetable_id' => $category->id,
                'budgetable_type' => Category::class,
                'target_amount' => $p['amount'],
            ]);
        }

        foreach (config('bendaharaku.budget.expense_groups') as $key => $name) {
            $categoryIds = collect($plan)
                ->filter(fn ($p) => $p['group'] === $key)
                ->map(fn ($p, $categoryName) => $cats->get($categoryName)?->id)
                ->filter()
                ->values()
                ->all();

            if ($categoryIds !== []) {
                BudgetExpenseGroup::create([
                    'budget_group_id' => $budget->id,
                    'group_key' => $key,
                    'group_name' => $name,
                    'category_ids' => $categoryIds,
                ]);
            }
        }
    }

    private function ensureHistoricalTransactions(User $user, Collection $types): void
    {
        $wallets = Wallet::where('user_id', $user->id)->get()->keyBy('name');
        $cats = Category::where('user_id', $user->id)->get()->keyBy('category_name');

        $externalId = $wallets->get('External System')?->id;
        $merchantId = $wallets->get('Merchant System')?->id;
        $debtId = $wallets->get('System Hutang')?->id;
        $receivableId = $wallets->get('System Piutang')?->id;

        $expense = $types['Expense'];
        $income = $types['Income'];
        $debt = $types['Debt'];

        // Profil: mahasiswa — pemasukan 3-4 jt/bulan, pengeluaran 2.5-3 jt/bulan
        for ($back = 2; $back >= 0; $back--) {
            $monthStart = Carbon::now()->startOfMonth()->subMonths($back);
            $rows = [];

            // ── Pemasukan ──
            $rows[] = ['amount' => 3000000, 'subject' => 'Uang saku bulanan dari orang tua', 'category' => 'Gaji', 'wallet' => 'Mandiri', 'direction' => $income, 'day' => 1];
            $rows[] = ['amount' => $back % 2 === 0 ? 850000 : 650000, 'subject' => 'Project freelance design', 'category' => 'Freelance', 'wallet' => 'BCA', 'direction' => $income, 'day' => 12];
            if ($back === 1) {
                $rows[] = ['amount' => 400000, 'subject' => 'Hasil jual barang online', 'category' => 'Pendapatan Lain', 'wallet' => 'Dana', 'direction' => $income, 'day' => 24];
            }

            // ── Makan & Minum (2x/hari di hari tertentu) ──
            $mealDays = [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28];
            $mealAmounts = [15000, 20000, 18000, 15000, 22000, 18000, 15000, 25000, 20000, 15000, 25000, 18000, 15000, 20000];
            foreach ($mealDays as $i => $day) {
                $rows[] = ['amount' => $mealAmounts[$i], 'subject' => 'Makan siang', 'category' => 'Makan & Minum', 'wallet' => 'Dana', 'direction' => $expense, 'day' => $day];
                $rows[] = ['amount' => $mealAmounts[$i] - 3000, 'subject' => 'Makan malam', 'category' => 'Makan & Minum', 'wallet' => 'Cash', 'direction' => $expense, 'day' => $day];
            }

            // ── Belanja bahan masakan (mingguan) ──
            $groceries = [150000, 180000, 140000, 165000];
            foreach ($groceries as $i => $amount) {
                $rows[] = ['amount' => $amount, 'subject' => 'Belanja bahan masakan', 'category' => 'Belanja', 'wallet' => 'Cash', 'direction' => $expense, 'day' => [3, 9, 17, 25][$i]];
            }

            // ── Transportasi ──
            $transit = [22000, 18000, 25000, 20000, 15000];
            foreach ($transit as $i => $amount) {
                $rows[] = ['amount' => $amount, 'subject' => 'Naik ojek online', 'category' => 'Transportasi', 'wallet' => 'Dana', 'direction' => $expense, 'day' => [5, 10, 16, 21, 26][$i]];
            }
            $rows[] = ['amount' => 100000, 'subject' => 'Isi bensin motor', 'category' => 'Transportasi', 'wallet' => 'Cash', 'direction' => $expense, 'day' => 24];

            // ── Tagihan rutin ──
            $rows[] = ['amount' => 165000, 'subject' => 'Bayar listrik & air', 'category' => 'Listrik & Air', 'wallet' => 'BCA', 'direction' => $expense, 'day' => 7];
            $rows[] = ['amount' => 120000, 'subject' => 'Paket internet & pulsa', 'category' => 'Pulsa & Internet', 'wallet' => 'Dana', 'direction' => $expense, 'day' => 6];
            $rows[] = ['amount' => 500000, 'subject' => 'SPP bulanan', 'category' => 'Pendidikan', 'wallet' => 'Mandiri', 'direction' => $expense, 'day' => 9];

            // ── Gaya hidup ──
            $rows[] = ['amount' => 60000, 'subject' => 'Langganan streaming', 'category' => 'Hiburan', 'wallet' => 'BCA', 'direction' => $expense, 'day' => 12];
            $rows[] = ['amount' => 49000, 'subject' => 'Top up game', 'category' => 'Hiburan', 'wallet' => 'Dana', 'direction' => $expense, 'day' => 21];
            if ($back !== 1) {
                $rows[] = ['amount' => 55000, 'subject' => 'Nonton bioskop', 'category' => 'Hiburan', 'wallet' => 'Dana', 'direction' => $expense, 'day' => 27];
            }

            // ── Tidak rutin ──
            if ($back === 1) {
                $rows[] = ['amount' => 90000, 'subject' => 'Beli obat & vitamin', 'category' => 'Kesehatan', 'wallet' => 'Cash', 'direction' => $expense, 'day' => 15];
            }
            if ($back === 0) {
                $rows[] = ['amount' => 150000, 'subject' => 'Beli kaos baru', 'category' => 'Pakaian', 'wallet' => 'BCA', 'direction' => $expense, 'day' => 22];
            }
            $rows[] = ['amount' => 50000, 'subject' => 'Donasi', 'category' => 'Donasi', 'wallet' => 'Cash', 'direction' => $expense, 'day' => 29];

            // ── Hutang ──
            $rows[] = ['amount' => 150000, 'subject' => 'Cicil hutang Budi', 'category' => 'Bayar Cicilan Hutang', 'wallet' => 'Cash', 'direction' => $debt, 'day' => 20, 'to' => $debtId];

            foreach ($rows as $t) {
                $date = $monthStart->copy()->addDays($t['day'] - 1);
                TransactionLog::create([
                    'user_id' => $user->id,
                    'reference_number' => 'TRX-TEST-'.strtoupper(Str::random(8)),
                    'date' => $date,
                    'type_id' => $t['direction'],
                    'category_id' => $cats->get($t['category'])?->id,
                    'source_wallet_id' => $t['direction'] === $income ? $externalId : $wallets->get($t['wallet'])?->id,
                    'destination_wallet_id' => $t['direction'] === $income ? $wallets->get($t['wallet'])?->id : ($t['to'] ?? $merchantId),
                    'amount' => $t['amount'],
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'subject' => $t['subject'],
                    'notes' => $t['subject'],
                    'is_cleared' => true,
                ]);
            }
        }
    }
}
