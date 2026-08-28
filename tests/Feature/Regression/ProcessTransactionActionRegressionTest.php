<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use App\Actions\ProcessTransactionAction;
use App\Enums\TransactionSource;
use App\Models\TransactionLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessTransactionActionRegressionTest extends TestCase
{
    use RefreshDatabase;
    use RegressionTestHelpers;

    private ProcessTransactionAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRegressionData();
        $this->action = $this->app->make(ProcessTransactionAction::class);
    }

    public function test_create_expense_deducts_source_and_credits_merchant(): void
    {
        $tx = $this->action->create([
            'amount' => 25000,
            'type' => 'expense',
            'category_id' => $this->foodCategory->id,
            'source_wallet_id' => $this->cashWallet->id,
            'destination_wallet_id' => $this->merchantWallet->id,
            'subject' => 'Budi',
            'notes' => 'beli siomay',
            'is_cleared' => true,
            'date' => Carbon::today()->toDateString(),
        ], $this->user->id);

        $this->assertInstanceOf(TransactionLog::class, $tx);
        $this->assertEquals(25000, $tx->amount);
        $this->assertTrue($tx->is_cleared);
        $this->assertBalanceEquals($this->cashWallet, 475000);
    }

    public function test_create_income_credits_source(): void
    {
        $tx = $this->action->create([
            'amount' => 3000000,
            'type' => 'income',
            'category_id' => $this->salaryCategory->id,
            'source_wallet_id' => $this->externalWallet->id,
            'destination_wallet_id' => $this->cashWallet->id,
            'subject' => 'Budi',
            'notes' => 'gaji bulan ini',
            'is_cleared' => true,
            'date' => Carbon::today()->toDateString(),
        ], $this->user->id);

        $this->assertInstanceOf(TransactionLog::class, $tx);
        $this->assertEquals(3000000, $tx->amount);
        $this->assertBalanceEquals($this->cashWallet, 3500000);
    }

    public function test_update_transaction_adjusts_balances(): void
    {
        $tx = $this->action->create([
            'amount' => 25000,
            'type' => 'expense',
            'category_id' => $this->foodCategory->id,
            'source_wallet_id' => $this->cashWallet->id,
            'destination_wallet_id' => $this->merchantWallet->id,
            'subject' => 'Budi',
            'notes' => 'beli siomay',
            'is_cleared' => true,
            'date' => Carbon::today()->toDateString(),
        ], $this->user->id);

        $updated = $this->action->update($tx, [
            'date' => $tx->date->toDateString(),
            'amount' => 30000,
            'source_wallet_id' => $this->cashWallet->id,
            'destination_wallet_id' => $this->merchantWallet->id,
            'transaction_type' => 'expense',
            'notes' => 'beli siomay + es teh',
        ]);

        $this->assertEquals(30000, $updated->amount);
        $this->assertEquals('beli siomay + es teh', $updated->notes);
        $this->assertBalanceEquals($this->cashWallet, 470000);
    }

    public function test_confirm_transaction_sets_is_cleared_true(): void
    {
        $tx = $this->action->create([
            'amount' => 25000,
            'type' => 'expense',
            'category_id' => $this->foodCategory->id,
            'source_wallet_id' => $this->cashWallet->id,
            'destination_wallet_id' => $this->merchantWallet->id,
            'subject' => 'Budi',
            'notes' => 'beli siomay',
            'is_cleared' => false,
            'date' => Carbon::today()->toDateString(),
        ], $this->user->id);

        $confirmed = $this->action->confirm($tx);

        $this->assertTrue($confirmed->is_cleared);
        $this->assertBalanceEquals($this->cashWallet, 475000);
    }

    public function test_delete_transaction_reverses_balances(): void
    {
        $tx = $this->action->create([
            'amount' => 25000,
            'type' => 'expense',
            'category_id' => $this->foodCategory->id,
            'source_wallet_id' => $this->cashWallet->id,
            'destination_wallet_id' => $this->merchantWallet->id,
            'subject' => 'Budi',
            'notes' => 'beli siomay',
            'is_cleared' => true,
            'date' => Carbon::today()->toDateString(),
        ], $this->user->id);

        $deleted = $this->action->delete($tx);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('transaction_logs', ['id' => $tx->id]);
        $this->assertBalanceEquals($this->cashWallet, 500000);
    }

    public function test_create_transaction_stores_correct_type_and_source(): void
    {
        $tx = $this->action->create([
            'amount' => 50000,
            'type' => 'expense',
            'category_id' => $this->foodCategory->id,
            'source_wallet_id' => $this->cashWallet->id,
            'destination_wallet_id' => $this->merchantWallet->id,
            'subject' => 'Budi',
            'notes' => 'bakso',
            'is_cleared' => true,
            'date' => Carbon::today()->toDateString(),
        ], $this->user->id, 'TRX', TransactionSource::SYSTEM);

        $this->assertEquals('Expense', $tx->type->name);
        $this->assertNotNull($tx->reference_number);
        $this->assertStringStartsWith('TRX', $tx->reference_number);
    }

    public function test_create_transfer_moves_balance_between_user_wallets(): void
    {
        $this->action->create([
            'amount' => 200000,
            'type' => 'transfer',
            'source_wallet_id' => $this->bcaWallet->id,
            'destination_wallet_id' => $this->cashWallet->id,
            'subject' => 'Budi',
            'notes' => 'pindah saldo',
            'is_cleared' => true,
            'date' => Carbon::today()->toDateString(),
        ], $this->user->id);

        $this->assertBalanceEquals($this->bcaWallet, 800000);
        $this->assertBalanceEquals($this->cashWallet, 700000);
    }

    public function test_create_debit_uses_debt_and_receivable_system_wallets(): void
    {
        $debtCat = $this->user->categories()->where('system_key', 'LOAN')->first();
        $debtSystemWallet = $this->user->wallets()->where('name', 'Hutang System')->first();

        $tx = $this->action->create([
            'amount' => 500000,
            'type' => 'debt',
            'category_id' => $debtCat->id,
            'source_wallet_id' => $this->cashWallet->id,
            'destination_wallet_id' => $debtSystemWallet->id,
            'subject' => 'Budi',
            'notes' => 'pinjam duit',
            'is_cleared' => true,
            'date' => Carbon::today()->toDateString(),
        ], $this->user->id);

        $this->assertEquals('Debt', $tx->type->name);
        $this->assertBalanceEquals($this->cashWallet, 0);
    }
}
