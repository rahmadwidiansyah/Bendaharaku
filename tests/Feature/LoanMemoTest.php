<?php

namespace Tests\Feature;

use App\Actions\ProcessTransactionAction;
use App\Enums\TransactionSource;
use App\Models\User;
use App\Services\Loan\ActiveLoanCycleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoanMemoTest extends TestCase
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

    public function test_repeat_call_on_same_instance_hits_memo()
    {
        $this->loanViaHttp('BUDI', 100000);
        $svc = app(ActiveLoanCycleService::class);

        $count = 0;
        DB::listen(function ($q) use (&$count) {
            if (str_contains($q->sql, 'transaction_logs') && str_contains($q->sql, 'system_key')) {
                $count++;
            }
        });

        $first = $svc->calculateForUser($this->user);
        $second = $svc->calculateForUser($this->user);

        $this->assertEquals($first, $second);
        $this->assertEquals(1, $count, 'Panggilan kedua harus dari memo, tanpa pindai ulang.');
    }

    public function test_fresh_instances_never_share_stale_state()
    {
        // Memo instance (bukan statis): resolusi baru tidak mewarisi status
        // lama — tidak ada kontaminasi antar request/job/test dalam satu proses.
        $this->assertEquals(0, app(ActiveLoanCycleService::class)->calculateForUser($this->user)['total_debt']);

        $this->loanViaHttp('BUDI', 100000);

        $this->assertEquals(100000, app(ActiveLoanCycleService::class)->calculateForUser($this->user)['total_debt']);
        $this->assertEquals(100000, app(ActiveLoanCycleService::class)->calculateForUser($this->user)['total_debt']);
    }

    public function test_memo_isolated_per_user()
    {
        $this->loanViaHttp('BUDI', 100000);
        $other = User::factory()->create();
        $svc = app(ActiveLoanCycleService::class);

        $this->assertEquals(100000, $svc->calculateForUser($this->user)['total_debt']);
        $this->assertEquals(0, $svc->calculateForUser($other)['total_debt']);
    }

    public function test_action_write_visible_to_later_resolutions()
    {
        $svc = app(ActiveLoanCycleService::class);
        $liquid = $this->user->wallets()->where('group_type', 'Liquid')->orderBy('id')->firstOrFail();
        $hutang = $this->user->wallets()->where('group_type', 'System')->orderBy('id')->firstOrFail();

        $this->assertEquals(0, $svc->calculateForUser($this->user)['total_debt']);

        app(ProcessTransactionAction::class)->create(
            data: [
                'date' => now()->format('Y-m-d'), 'category_id' => null,
                'source_wallet_id' => $hutang->id, 'destination_wallet_id' => $liquid->id,
                'amount' => 75000, 'transaction_type' => 'debt', 'debt_sub_type' => 'income',
                'subject' => 'BUDI',
            ],
            userId: $this->user->id,
            sourcePrefix: 'TRX',
            source: TransactionSource::SYSTEM,
        );

        $this->assertEquals(75000, app(ActiveLoanCycleService::class)->calculateForUser($this->user)['total_debt']);
    }
}
