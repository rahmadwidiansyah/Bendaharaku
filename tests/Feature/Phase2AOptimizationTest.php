<?php

namespace Tests\Feature;

use App\Models\BudgetGroup;
use App\Models\Category;
use App\Models\LoanBalance;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\Budgeting\AIBudgetService;
use App\Services\Loan\ActiveLoanCycleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase2AOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    private function loanViaHttp(string $subject, int $amount): void
    {
        $liquid = $this->user->wallets()->where('group_type', 'Liquid')->orderBy('id')->firstOrFail();
        $hutang = $this->user->wallets()->where('group_type', 'System')->orderBy('id')->firstOrFail();

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'date' => now()->format('Y-m-d'),
            'category_id' => null,
            'source_wallet_id' => $hutang->id,
            'destination_wallet_id' => $liquid->id,
            'amount' => $amount,
            'transaction_type' => 'debt',
            'debt_sub_type' => 'income',
            'subject' => $subject,
        ])->assertRedirect(route('dashboard'));
    }

    private function payViaHttp(string $subject, int $amount): void
    {
        $liquid = $this->user->wallets()->where('group_type', 'Liquid')->orderBy('id')->firstOrFail();
        $hutang = $this->user->wallets()->where('group_type', 'System')->orderBy('id')->firstOrFail();

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'date' => now()->format('Y-m-d'),
            'category_id' => null,
            'source_wallet_id' => $liquid->id,
            'destination_wallet_id' => $hutang->id,
            'amount' => $amount,
            'transaction_type' => 'debt',
            'debt_sub_type' => 'expense',
            'subject' => $subject,
        ])->assertRedirect(route('dashboard'));
    }

    public function test_dashboard_totals_match_service_and_single_loan_scan()
    {
        $this->loanViaHttp('DINA', 200000);
        $this->payViaHttp('DINA', 50000);

        $expected = (float) LoanBalance::where('user_id', $this->user->id)->where('loan_type', 'debt')->sum('balance');
        $this->assertEquals(150000, $expected);

        // Hitung pembanding SEBELUM listener agar pindai milik test sendiri
        // tidak ikut terhitung dalam jendela pengukuran dashboard.
        $service = app(ActiveLoanCycleService::class)->calculateForUser($this->user);

        $sqls = [];
        DB::listen(function ($q) use (&$sqls) {
            $sqls[] = $q->sql;
        });

        $response = $this->actingAs($this->user)->get(route('dashboard'));
        $response->assertOk();

        // Total halaman harus identik dengan sumber kebenaran (service + tabel ringkasan).
        // Perbandingan longgar tipe numerik (JSON dapat menormalkan 150000.0 → 150000).
        $response->assertInertia(fn ($page) => $page
            ->where('totalHutang', fn ($v) => $v == $expected)
            ->where('totalHutang', fn ($v) => $v == $service['total_debt'])
            ->where('totalPiutang', fn ($v) => $v == $service['total_receivable']));

        // Pindai histori loan (select transaction_logs + whereHas system_key)
        // hanya boleh terjadi SEKALI per request dashboard.
        $scans = collect($sqls)->filter(fn ($sql) => str_contains($sql, 'transaction_logs') && str_contains($sql, 'system_key'))->count();
        $this->assertEquals(1, $scans, 'Dashboard memindai histori loan lebih dari sekali.');
    }

    public function test_budget_summary_grouped_exact_and_bounded_queries()
    {
        $expenseType = TransactionType::where('name', 'Expense')->firstOrFail();
        $wallet = $this->user->wallets()->orderBy('id')->firstOrFail();
        $cats = [];
        for ($i = 0; $i < 5; $i++) {
            $cats[] = Category::create([
                'user_id' => $this->user->id,
                'type_id' => $expenseType->id,
                'category_name' => 'Bench Cat '.$i,
                'icon' => 'cart',
            ]);
        }

        $spent = [120000, 0, 45500, 300000, 99999];
        foreach ($cats as $i => $cat) {
            if ($spent[$i] > 0) {
                TransactionLog::create([
                    'user_id' => $this->user->id,
                    'reference_number' => 'TRX-'.strtoupper(Str::random(10)),
                    'date' => '2026-08-1'.$i,
                    'type_id' => $expenseType->id,
                    'category_id' => $cat->id,
                    'source_wallet_id' => $wallet->id,
                    'amount' => $spent[$i],
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'subject' => 'Bench',
                    'is_cleared' => true,
                ]);
            }
        }
        // Log milik pengguna lain tidak boleh memengaruhi ringkasan.
        $other = User::factory()->create();
        $otherCat = Category::create(['user_id' => $other->id, 'type_id' => $expenseType->id, 'category_name' => 'X', 'icon' => 'x']);
        $otherWallet = $other->wallets()->orderBy('id')->firstOrFail();
        TransactionLog::create([
            'user_id' => $other->id,
            'reference_number' => 'TRX-'.strtoupper(Str::random(10)),
            'date' => '2026-08-10',
            'type_id' => $expenseType->id,
            'category_id' => $otherCat->id,
            'source_wallet_id' => $otherWallet->id,
            'amount' => 777777,
            'balance_before' => 0,
            'balance_after' => 0,
            'subject' => 'Other',
            'is_cleared' => true,
        ]);

        $group = BudgetGroup::create([
            'user_id' => $this->user->id, 'period_month' => 8, 'period_year' => 2026,
            'total_budget_amount' => 0, 'generated_by' => 'manual',
        ]);
        $itemIds = [];
        $targets = [200000, 100000, 45500, 250000, 99999, 50000];
        foreach ($cats as $i => $cat) {
            $itemIds[$i] = $group->items()->create([
                'budgetable_id' => $cat->id, 'budgetable_type' => Category::class, 'target_amount' => $targets[$i],
            ])->id;
        }
        // Item bertipe TransactionType + item bertipe tak dikenal (semantik lama dipertahankan).
        $typeItem = $group->items()->create([
            'budgetable_id' => $expenseType->id, 'budgetable_type' => TransactionType::class, 'target_amount' => 1000000,
        ]);
        $unknownItem = $group->items()->create([
            'budgetable_id' => 1, 'budgetable_type' => 'App\\Models\\DoesNotExist', 'target_amount' => 1000,
        ]);

        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });
        $summary = app(AIBudgetService::class)->getBudgetSummary($group->fresh());

        // Nilai eksak per item (termasuk kerusakan pembulatan/persen).
        foreach ($cats as $i => $cat) {
            $this->assertSame((float) $spent[$i], $summary[$itemIds[$i]]['spent']);
            $this->assertSame((float) ($targets[$i] - $spent[$i]), $summary[$itemIds[$i]]['remaining']);
        }
        $this->assertSame(60.0, $summary[$itemIds[0]]['percentage']); // 120000/200000
        $this->assertSame(100.0, $summary[$itemIds[2]]['percentage']); // 45500/45500, capped 100
        $this->assertSame(0.0, $summary[$itemIds[1]]['percentage']); // target>0, spent 0
        $this->assertSame(0.0, $summary[$unknownItem->id]['spent']);
        $this->assertLessThanOrEqual(5, $count, 'Ringkasan budget melebihi 5 query.');
    }
}
