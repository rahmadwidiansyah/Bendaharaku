<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AiParseLog;
use App\Models\BudgetGroup;
use App\Models\BudgetItem;
use App\Models\Category;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $cashWalletId;

    protected function setUp(): void
    {
        parent::setUp();

        // Tipe transaksi id dari DB (jangan hardcode — id bisa bergeser saat reseed)
        foreach ([['Income'], ['Expense'], ['Transfer'], ['Debt'], ['Receivable']] as [$name]) {
            TransactionType::create(['name' => $name]);
        }

        $this->user = User::factory()->create([
            'name' => 'Tester',
            'email_verified_at' => now(),
            'ui_theme' => 'm3',
        ]);
        $this->user->wallets()->create([
            'name' => 'Dompet Cash', 'keyword' => 'cash',
            'group_type' => 'Liquid', 'balance' => 1000000,
        ]);
        $this->cashWalletId = $this->user->wallets()->where('keyword', 'cash')->value('id');
    }

    /** @test */
    public function test_budget_null_ketika_belum_ada_budget_bulan_ini(): void
    {
        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('budget', null));
    }

    /** @test */
    public function test_budget_widget_mengembalikan_total_dan_top_3_kategori(): void
    {
        $expense = TransactionType::where('name', 'Expense')->first();
        $group = BudgetGroup::create([
            'user_id' => $this->user->id,
            'period_month' => now()->month,
            'period_year' => now()->year,
            'total_budget_amount' => 900000,
            'generated_by' => 'manual',
        ]);

        $categories = [];
        foreach ([['Makan', 400000], ['Transport', 300000], ['Hiburan', 200000], ['Belanja', 100000]] as [$name, $target]) {
            $cat = Category::create([
                'user_id' => $this->user->id,
                'category_name' => $name,
                'type_id' => $expense->id,
            ]);
            $categories[$name] = $cat;
            BudgetItem::create([
                'budget_group_id' => $group->id,
                'budgetable_type' => Category::class,
                'budgetable_id' => $cat->id,
                'target_amount' => $target,
            ]);
        }

        // Pengeluaran bulan ini: Makan 200k, Transport 150k
        $this->user->transactionLogs()->createMany([
            [
                'date' => now()->toDateString(), 'amount' => 200000,
                'category_id' => $categories['Makan']->id, 'type_id' => $expense->id,
                'source_wallet_id' => $this->cashWalletId, 'destination_wallet_id' => null,
                'balance_before' => 0, 'balance_after' => 0,
                'reference_number' => 'TEST-1',
                'is_cleared' => true, 'subject' => 't',
            ],
            [
                'date' => now()->toDateString(), 'amount' => 150000,
                'category_id' => $categories['Transport']->id, 'type_id' => $expense->id,
                'source_wallet_id' => $this->cashWalletId, 'destination_wallet_id' => null,
                'balance_before' => 0, 'balance_after' => 0,
                'reference_number' => 'TEST-2',
                'is_cleared' => true, 'subject' => 't',
            ],
        ]);

        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('budget', fn ($b) => $b !== null
                && count($b['items']) === 3
                && abs((float) $b['totalBudget'] - 1000000) < 1
                && abs((float) $b['totalSpent'] - 350000) < 1
                // Top-3 by target: Makan(400k), Transport(300k), Hiburan(200k) — Belanja(100k) terpotong
                && $b['items'][0]['name'] === 'Makan'
                && abs((float) $b['items'][0]['spent'] - 200000) < 1));
    }

    /** @test */
    public function test_ai_usage_null_ketika_belum_ada_pemakaian_hari_ini(): void
    {
        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('aiUsage', null));
    }

    /** @test */
    public function test_ai_usage_widget_menghitung_pesan_token_dan_success_rate(): void
    {
        AiParseLog::create([
            'user_id' => $this->user->id, 'provider' => 'python-nlp', 'model' => 'local',
            'input_text' => 'a', 'is_success' => true, 'status' => 'parsed',
            'raw_confidence' => 0.9, 'final_confidence' => 0.9, 'total_tokens' => 0,
        ]);
        AiParseLog::create([
            'user_id' => $this->user->id, 'provider' => 'gemini', 'model' => 'gemini-x',
            'input_text' => 'b', 'is_success' => true, 'status' => 'parsed',
            'raw_confidence' => 0.9, 'final_confidence' => 0.9, 'total_tokens' => 1200,
        ]);
        AiParseLog::create([
            'user_id' => $this->user->id, 'provider' => 'gemini', 'model' => 'gemini-x',
            'input_text' => 'c', 'is_success' => false, 'status' => 'failed',
            'raw_confidence' => 0, 'final_confidence' => 0, 'total_tokens' => 300,
            'error_message' => 'x',
        ]);

        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('aiUsage', fn ($u) => $u !== null
                && $u['requests'] === 3
                && $u['tokens'] === 1500
                && abs((float) $u['successRate'] - 66.7) < 0.1));
    }
}
