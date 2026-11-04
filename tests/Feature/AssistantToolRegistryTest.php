<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AiToolLog;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\AI\Assistant\ToolRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssistantToolRegistryTest extends TestCase
{
    use RefreshDatabase;

    private function registry(): ToolRegistry
    {
        return app(ToolRegistry::class);
    }

    private function makeTransaction(User $user, array $overrides = []): TransactionLog
    {
        $expenseType = TransactionType::where('name', 'Expense')->firstOrFail();
        $category = $user->categories()->where('type_id', $expenseType->id)->firstOrFail();
        $wallet = $user->wallets()->where('group_type', 'Liquid')->firstOrFail();

        return TransactionLog::create(array_merge([
            'reference_number' => 'TEST-'.Str::uuid(),
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'type_id' => $expenseType->id,
            'category_id' => $category->id,
            'source_wallet_id' => $wallet->id,
            'destination_wallet_id' => null,
            'amount' => 50000,
            'balance_before' => 100000,
            'balance_after' => 50000,
            'subject' => 'Makan siang',
            'notes' => 'test',
            'is_cleared' => true,
        ], $overrides));
    }

    /** @test */
    public function test_definisi_tool_tersedia_dan_provider_agnostic(): void
    {
        $user = User::factory()->create();

        $definitions = $this->registry()->forUser($user);
        $names = array_column($definitions, 'name');

        $this->assertCount(6, $definitions);
        $this->assertSame([
            'get_wallets',
            'get_categories',
            'get_transactions',
            'get_monthly_summary',
            'compare_periods',
            'get_financial_overview',
        ], $names);

        foreach ($definitions as $definition) {
            $this->assertArrayHasKey('description', $definition);
            $this->assertArrayHasKey('parameters', $definition);
            $this->assertSame('object', $definition['parameters']['type']);
        }
    }

    /** @test */
    public function test_get_wallets_hanya_mengembalikan_dompet_milik_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userB->wallets()->create([
            'name' => 'Dompet Rahasia B',
            'balance' => 999999,
            'group_type' => 'Liquid',
        ]);

        $result = $this->registry()->execute($userA, 'get_wallets', [], 'trace-wallet');

        $this->assertTrue($result['ok']);
        $names = array_column($result['data']['wallets'], 'name');
        $this->assertContains('Dompet Cash', $names);
        $this->assertNotContains('Dompet Rahasia B', $names);
        $this->assertGreaterThan(0, $result['data']['count']);
    }

    /** @test */
    public function test_parameter_tidak_valid_ditolak_dan_tercatat(): void
    {
        $user = User::factory()->create();

        $result = $this->registry()->execute(
            $user,
            'get_transactions',
            ['limit' => 999],
            'trace-invalid'
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('validation_error', $result['error']);
        $this->assertArrayHasKey('limit', $result['errors']);

        $log = AiToolLog::where('trace_id', 'trace-invalid')->firstOrFail();
        $this->assertSame('validation_error', $log->status);
        $this->assertSame(999, $log->params['limit']);
    }

    /** @test */
    public function test_get_transactions_dibatasi_dan_tidak_bocor_antar_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->makeTransaction($userA, ['amount' => 10000]);
        $this->makeTransaction($userA, ['amount' => 20000]);
        $this->makeTransaction($userA, ['amount' => 30000]);
        $bTransaction = $this->makeTransaction($userB, ['amount' => 777000]);

        $result = $this->registry()->execute(
            $userA,
            'get_transactions',
            ['limit' => 2],
            'trace-transactions'
        );

        $this->assertTrue($result['ok']);
        $this->assertCount(2, $result['data']['transactions']);
        $ids = array_column($result['data']['transactions'], 'id');
        $this->assertNotContains($bTransaction->id, $ids);

        $log = AiToolLog::where('trace_id', 'trace-transactions')->firstOrFail();
        $this->assertSame('success', $log->status);
        $this->assertSame(2, $log->result_rows);
    }

    /** @test */
    public function test_get_transactions_sort_amount_mengurutkan_nominal(): void
    {
        $user = User::factory()->create();

        $this->makeTransaction($user, ['amount' => 50000, 'subject' => 'Kecil']);
        $this->makeTransaction($user, ['amount' => 200000, 'subject' => 'Besar']);
        $this->makeTransaction($user, ['amount' => 75000, 'subject' => 'Tengah']);

        $desc = $this->registry()->execute($user, 'get_transactions', [
            'sort' => 'amount_desc',
        ], 'trace-sort-desc');

        $this->assertTrue($desc['ok']);
        $subjectsDesc = array_column($desc['data']['transactions'], 'subject');
        $this->assertSame(['Besar', 'Tengah', 'Kecil'], $subjectsDesc);

        $asc = $this->registry()->execute($user, 'get_transactions', [
            'sort' => 'amount_asc',
        ], 'trace-sort-asc');

        $this->assertTrue($asc['ok']);
        $subjectsAsc = array_column($asc['data']['transactions'], 'subject');
        $this->assertSame(['Kecil', 'Tengah', 'Besar'], $subjectsAsc);
    }

    /** @test */
    public function test_get_transactions_melaporkan_draft_count_tanpa_menyertakan_draft(): void
    {
        $user = User::factory()->create();

        $this->makeTransaction($user, ['amount' => 50000, 'subject' => 'Cleared']);
        $this->makeTransaction($user, [
            'amount' => 999000,
            'subject' => 'Draft belum konfirmasi',
            'is_cleared' => false,
        ]);

        $result = $this->registry()->execute($user, 'get_transactions', [], 'trace-draft');

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['data']['count']);
        $this->assertSame(1, $result['data']['draft_count']);
        $subjects = array_column($result['data']['transactions'], 'subject');
        $this->assertContains('Cleared', $subjects);
        $this->assertNotContains('Draft belum konfirmasi', $subjects);
    }

    /** @test */
    public function test_ringkasan_bulanan_melaporkan_draft_count(): void
    {
        $user = User::factory()->create();
        $this->makeTransaction($user, ['amount' => 50000]);
        $this->makeTransaction($user, [
            'amount' => 999000,
            'subject' => 'Draft belum konfirmasi',
            'is_cleared' => false,
        ]);

        $result = $this->registry()->execute($user, 'get_monthly_summary', [], 'trace-summary-draft');

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['data']['transaction_count']);
        $this->assertSame(1, $result['data']['draft_count']);
    }

    /** @test */
    public function test_ringkasan_bulanan_melaporkan_transfer_count_terpisah(): void
    {
        $user = User::factory()->create();
        $transferType = TransactionType::where('name', 'Transfer')->firstOrFail();
        $transferCategory = $user->categories()
            ->where('type_id', $transferType->id)
            ->where('system_key', 'TRANSFER')
            ->firstOrFail();
        $wallets = $user->wallets()->where('group_type', 'Liquid')->get();
        $wallets->first()->update(['balance' => 200000]);
        $destination = $user->wallets()->create([
            'name' => 'Dompet Kedua',
            'balance' => 0,
            'group_type' => 'Liquid',
        ]);

        $this->makeTransaction($user, ['amount' => 50000]);
        TransactionLog::create([
            'reference_number' => 'TEST-'.Str::uuid(),
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'type_id' => $transferType->id,
            'category_id' => $transferCategory->id,
            'source_wallet_id' => $wallets->first()->id,
            'destination_wallet_id' => $destination->id,
            'amount' => 75000,
            'balance_before' => 200000,
            'balance_after' => 125000,
            'subject' => 'Pindah saldo',
            'notes' => 'test transfer',
            'is_cleared' => true,
        ]);

        $result = $this->registry()->execute($user, 'get_monthly_summary', [], 'trace-summary-transfer');

        $this->assertTrue($result['ok']);
        // Hanya expense yang masuk transaction_count; transfer terpisah.
        $this->assertSame(1, $result['data']['transaction_count']);
        $this->assertSame(1, $result['data']['transfer_count']);
    }

    /** @test */
    public function test_deskripsi_tool_agregat_memandu_pemilihan_bulan(): void
    {
        $user = User::factory()->create();
        $definitions = array_column($this->registry()->forUser($user), 'description', 'name');

        $this->assertStringContainsString('WAJIB isi year dan month', $definitions['get_monthly_summary']);
        $this->assertStringContainsString('bulan berjalan SAJA', $definitions['get_financial_overview']);
        $this->assertStringContainsString('get_monthly_summary', $definitions['get_financial_overview']);
    }

    /** @test */
    public function test_get_transactions_melaporkan_total_count_saat_terpotong(): void
    {
        $user = User::factory()->create();

        $this->makeTransaction($user, ['amount' => 10000]);
        $this->makeTransaction($user, ['amount' => 20000]);
        $this->makeTransaction($user, ['amount' => 30000]);

        $result = $this->registry()->execute($user, 'get_transactions', [
            'limit' => 2,
        ], 'trace-truncated');

        $this->assertTrue($result['ok']);
        $this->assertCount(2, $result['data']['transactions']);
        $this->assertSame(2, $result['data']['count']);
        $this->assertSame(3, $result['data']['total_count']);
    }

    /** @test */
    public function test_get_transactions_period_mengunci_bulan_yang_ditanyakan(): void
    {
        $user = User::factory()->create();

        // Transaksi Oktober (bulan lampau) + November (bulan berjalan).
        $this->makeTransaction($user, [
            'date' => now()->subMonthNoOverflow()->startOfMonth()->addDays(5)->toDateString(),
            'amount' => 111000,
            'subject' => 'Oktober',
        ]);
        $this->makeTransaction($user, [
            'date' => now()->toDateString(),
            'amount' => 222000,
            'subject' => 'November',
        ]);

        $result = $this->registry()->execute($user, 'get_transactions', [
            'period' => now()->subMonthNoOverflow()->format('Y-m'),
        ], 'trace-period');

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['data']['count']);
        $this->assertSame(0, $result['data']['draft_count']);
        $subjects = array_column($result['data']['transactions'], 'subject');
        $this->assertSame(['Oktober'], $subjects);
    }

    /** @test */
    public function test_get_transactions_period_di_masa_depan_ditolak(): void
    {
        $user = User::factory()->create();

        $result = $this->registry()->execute($user, 'get_transactions', [
            'period' => now()->addMonth()->format('Y-m'),
        ], 'trace-period-future');

        $this->assertFalse($result['ok']);
        $this->assertSame('validation_error', $result['error']);
    }

    /** @test */
    public function test_get_transactions_period_format_salah_ditolak(): void
    {
        $user = User::factory()->create();

        $result = $this->registry()->execute($user, 'get_transactions', [
            'period' => 'oktober',
        ], 'trace-period-bad');

        $this->assertFalse($result['ok']);
        $this->assertSame('validation_error', $result['error']);
    }

    /** @test */
    public function test_rentang_tanggal_lebih_dari_92_hari_ditolak(): void
    {        $user = User::factory()->create();

        $result = $this->registry()->execute($user, 'get_transactions', [
            'start_date' => now()->subDays(100)->toDateString(),
            'end_date' => now()->toDateString(),
        ], 'trace-range');

        $this->assertFalse($result['ok']);
        $this->assertSame('validation_error', $result['error']);
        $this->assertStringContainsString('92', $result['message']);
    }

    /** @test */
    public function test_ringkasan_bulanan_dan_perbandingan_periode(): void
    {
        $user = User::factory()->create();
        $expenseType = TransactionType::where('name', 'Expense')->firstOrFail();
        $incomeType = TransactionType::where('name', 'Income')->firstOrFail();
        $expenseCategory = $user->categories()->where('type_id', $expenseType->id)->firstOrFail();
        $incomeCategory = $user->categories()->where('type_id', $incomeType->id)->firstOrFail();
        $wallet = $user->wallets()->where('group_type', 'Liquid')->firstOrFail();

        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();

        $this->makeTransaction($user, [
            'date' => $thisMonth->toDateString(),
            'type_id' => $incomeType->id,
            'category_id' => $incomeCategory->id,
            'destination_wallet_id' => $wallet->id,
            'amount' => 1000000,
        ]);
        $this->makeTransaction($user, [
            'date' => $thisMonth->toDateString(),
            'type_id' => $expenseType->id,
            'category_id' => $expenseCategory->id,
            'amount' => 250000,
        ]);
        $this->makeTransaction($user, [
            'date' => $lastMonth->toDateString(),
            'type_id' => $expenseType->id,
            'category_id' => $expenseCategory->id,
            'amount' => 100000,
        ]);

        $summary = $this->registry()->execute($user, 'get_monthly_summary', [
            'year' => $thisMonth->year,
            'month' => $thisMonth->month,
        ], 'trace-summary');

        $this->assertTrue($summary['ok']);
        $this->assertSame(1000000.0, $summary['data']['income']);
        $this->assertSame(250000.0, $summary['data']['expense']);
        $this->assertSame(750000.0, $summary['data']['net']);
        $this->assertSame(2, $summary['data']['transaction_count']);

        $comparison = $this->registry()->execute($user, 'compare_periods', [
            'period_a' => $lastMonth->format('Y-m'),
            'period_b' => $thisMonth->format('Y-m'),
        ], 'trace-compare');

        $this->assertTrue($comparison['ok']);
        $this->assertSame(100000.0, $comparison['data']['period_a']['expense']);
        $this->assertSame(250000.0, $comparison['data']['period_b']['expense']);
        $this->assertSame(150000.0, $comparison['data']['difference']['expense']);
    }

    /** @test */
    public function test_financial_overview_menghitung_portofolio_dan_arus_kas(): void
    {
        $user = User::factory()->create();
        $liquid = $user->wallets()->where('group_type', 'Liquid')->firstOrFail();
        $liquid->update(['balance' => 150000]);
        $user->wallets()->create([
            'name' => 'Reksa Dana',
            'balance' => 500000,
            'group_type' => 'Asset',
        ]);

        $result = $this->registry()->execute($user, 'get_financial_overview', [], 'trace-overview');

        $this->assertTrue($result['ok']);
        $this->assertSame(650000.0, $result['data']['portfolio']['total_portfolio']);
        $this->assertSame(150000.0, $result['data']['portfolio']['total_liquid']);
        $this->assertSame(500000.0, $result['data']['portfolio']['total_invest']);
        $this->assertSame(0.0, $result['data']['cashflow_this_month']['expense']);
    }

    /** @test */
    public function test_param_liar_tidak_disimpan_di_log(): void
    {
        $user = User::factory()->create();

        $this->registry()->execute($user, 'get_wallets', [
            'api_key' => 'secret-should-not-be-logged',
            'user_id' => 999,
        ], 'trace-safe-params');

        $log = AiToolLog::where('trace_id', 'trace-safe-params')->firstOrFail();
        $this->assertArrayNotHasKey('api_key', $log->params);
        $this->assertArrayNotHasKey('user_id', $log->params);
    }

    /** @test */
    public function test_tool_tidak_dikenal_dikembalikan_sebagai_error(): void
    {
        $user = User::factory()->create();

        $result = $this->registry()->execute($user, 'delete_everything', [], 'trace-unknown');

        $this->assertFalse($result['ok']);
        $this->assertSame('unknown_tool', $result['error']);
        $this->assertDatabaseHas('ai_tool_logs', [
            'trace_id' => 'trace-unknown',
            'tool' => 'delete_everything',
            'status' => 'error',
        ]);
    }
}
