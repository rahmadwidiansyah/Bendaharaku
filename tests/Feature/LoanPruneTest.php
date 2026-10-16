<?php

namespace Tests\Feature;

use App\Models\LoanBalance;
use App\Models\User;
use App\Services\Loan\LoanBalanceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanPruneTest extends TestCase
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

    public function test_prune_removes_orphan_and_keeps_valid()
    {
        $this->loanViaHttp('BUDI', 100000);
        LoanBalance::create([
            'user_id' => $this->user->id, 'subject' => 'HANTU', 'loan_type' => 'debt',
            'balance' => 999000, 'opened_at' => now()->toDateString(),
            'last_transaction_at' => now()->toDateString(),
        ]);

        $pruned = app(LoanBalanceService::class)->pruneOrphans($this->user->id);

        $this->assertEquals(1, $pruned);
        $this->assertDatabaseMissing('loan_balances', [
            'user_id' => $this->user->id, 'subject' => 'HANTU',
        ]);
        $this->assertDatabaseHas('loan_balances', [
            'user_id' => $this->user->id, 'subject' => 'BUDI', 'balance' => 100000,
        ]);
    }

    public function test_prune_with_no_orphans_deletes_nothing()
    {
        $this->loanViaHttp('BUDI', 100000);

        $this->assertEquals(0, app(LoanBalanceService::class)->pruneOrphans($this->user->id));
        $this->assertDatabaseHas('loan_balances', [
            'user_id' => $this->user->id, 'subject' => 'BUDI',
        ]);
    }

    public function test_prune_is_scoped_per_user()
    {
        $other = User::factory()->create();
        LoanBalance::create([
            'user_id' => $other->id, 'subject' => 'HANTU', 'loan_type' => 'debt',
            'balance' => 1000, 'opened_at' => now()->toDateString(),
            'last_transaction_at' => now()->toDateString(),
        ]);

        app(LoanBalanceService::class)->pruneOrphans($this->user->id);

        $this->assertDatabaseHas('loan_balances', [
            'user_id' => $other->id, 'subject' => 'HANTU',
        ]);
    }

    public function test_command_prune_flag_works()
    {
        $this->loanViaHttp('BUDI', 100000);
        LoanBalance::create([
            'user_id' => $this->user->id, 'subject' => 'HANTU', 'loan_type' => 'debt',
            'balance' => 1000, 'opened_at' => now()->toDateString(),
            'last_transaction_at' => now()->toDateString(),
        ]);

        $this->artisan('loans:rebuild-balances', ['user_id' => $this->user->id, '--prune-orphans' => true])
            ->assertSuccessful();
        $this->assertDatabaseMissing('loan_balances', [
            'user_id' => $this->user->id, 'subject' => 'HANTU',
        ]);
        $this->assertDatabaseHas('loan_balances', [
            'user_id' => $this->user->id, 'subject' => 'BUDI',
        ]);
    }
}
