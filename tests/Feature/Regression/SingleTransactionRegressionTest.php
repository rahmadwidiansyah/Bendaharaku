<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use App\DTO\AIParseResult;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Services\AI\AIManager;
use App\Services\Chat\ChatTransactionOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Feature\Regression\RegressionTestHelpers;
use Tests\TestCase;

class SingleTransactionRegressionTest extends TestCase
{
    use RefreshDatabase;
    use RegressionTestHelpers;

    private ChatTransactionOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRegressionData();
    }

    public function test_expense_low_confidence_creates_draft(): void
    {
        $parsed = new ParsedTransaction(
            amount: 25000,
            transactionType: TransactionIntent::Expense,
            category: 'makan',
            sourceWallet: 'cash',
            isCleared: false
        );
        $mockAiResult = new AIParseResult(true, 0.40, null, $parsed);

        $this->instance(AIManager::class, Mockery::mock(AIManager::class, function (MockInterface $mock) use ($mockAiResult) {
            $mock->shouldReceive('parseTransaction')->once()->andReturn($mockAiResult);
        }));

        $this->orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $result = $this->orchestrator->process($this->user, 'beli makan 25rb', 'WEB');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_web_draft']);
        $this->assertNotNull($result['draft']);
        $this->assertEquals('pending', $result['draft']->status);
        $this->assertDatabaseHas('transaction_drafts', ['user_id' => $this->user->id, 'status' => 'pending']);
        $this->assertDatabaseCount('transaction_logs', 0);
        $this->assertBalanceEquals($this->cashWallet, 500000);
    }

    public function test_expense_high_confidence_executes_and_deducts_balance(): void
    {
        $parsed = new ParsedTransaction(
            amount: 15000,
            transactionType: TransactionIntent::Expense,
            category: 'makan',
            sourceWallet: 'cash',
            isCleared: true
        );
        $mockAiResult = new AIParseResult(true, 0.95, null, $parsed);

        $this->instance(AIManager::class, Mockery::mock(AIManager::class, function (MockInterface $mock) use ($mockAiResult) {
            $mock->shouldReceive('parseTransaction')->once()->andReturn($mockAiResult);
        }));

        $this->orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $result = $this->orchestrator->process($this->user, 'beli kopi 15rb cash', 'TEL');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['transaction']->is_cleared);
        $this->assertEquals(15000, $result['transaction']->amount);
        $this->assertBalanceEquals($this->cashWallet, 485000);
    }

    public function test_income_high_confidence_executes_and_adds_balance(): void
    {
        $parsed = new ParsedTransaction(
            amount: 5000000,
            transactionType: TransactionIntent::Income,
            category: 'gaji',
            isCleared: true
        );
        $mockAiResult = new AIParseResult(true, 0.95, null, $parsed);

        $this->instance(AIManager::class, Mockery::mock(AIManager::class, function (MockInterface $mock) use ($mockAiResult) {
            $mock->shouldReceive('parseTransaction')->once()->andReturn($mockAiResult);
        }));

        $this->orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $result = $this->orchestrator->process($this->user, 'gajian 5jt', 'TEL');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['transaction']->is_cleared);
        $this->assertEquals(5000000, $result['transaction']->amount);
        $this->assertBalanceEquals($this->cashWallet, 5500000);
    }

    public function test_transfer_high_confidence_moves_balance_between_wallets(): void
    {
        $parsed = new ParsedTransaction(
            amount: 500000,
            transactionType: TransactionIntent::Transfer,
            sourceWallet: 'bca',
            destinationWallet: 'cash',
            isCleared: true
        );
        $mockAiResult = new AIParseResult(true, 0.95, null, $parsed);

        $this->instance(AIManager::class, Mockery::mock(AIManager::class, function (MockInterface $mock) use ($mockAiResult) {
            $mock->shouldReceive('parseTransaction')->once()->andReturn($mockAiResult);
        }));

        $this->orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $result = $this->orchestrator->process($this->user, 'pindah 500rb dari BCA ke cash', 'TEL');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['transaction']->is_cleared);
        $this->assertEquals(500000, $result['transaction']->amount);
        $this->assertBalanceEquals($this->bcaWallet, 500000);
        $this->assertBalanceEquals($this->cashWallet, 1000000);
    }

    public function test_debt_high_confidence_uses_debt_system_wallet(): void
    {
        $parsed = new ParsedTransaction(
            amount: 200000,
            transactionType: TransactionIntent::Debt,
            category: 'ngutang',
            sourceWallet: 'cash',
            isCleared: true
        );
        $mockAiResult = new AIParseResult(true, 0.95, null, $parsed);

        $this->instance(AIManager::class, Mockery::mock(AIManager::class, function (MockInterface $mock) use ($mockAiResult) {
            $mock->shouldReceive('parseTransaction')->once()->andReturn($mockAiResult);
        }));

        $this->orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $result = $this->orchestrator->process($this->user, 'ngutang 200rb ke budi cash', 'TEL');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['transaction']->is_cleared);
        $this->assertEquals(200000, $result['transaction']->amount);
    }

    public function test_receivable_high_confidence_uses_receivable_system_wallet(): void
    {
        $parsed = new ParsedTransaction(
            amount: 150000,
            transactionType: TransactionIntent::Receivable,
            category: 'ngasih piutang',
            sourceWallet: 'cash',
            isCleared: true
        );
        $mockAiResult = new AIParseResult(true, 0.95, null, $parsed);

        $this->instance(AIManager::class, Mockery::mock(AIManager::class, function (MockInterface $mock) use ($mockAiResult) {
            $mock->shouldReceive('parseTransaction')->once()->andReturn($mockAiResult);
        }));

        $this->orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $result = $this->orchestrator->process($this->user, 'pinjamin 150rb ke rudi cash', 'TEL');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['transaction']->is_cleared);
        $this->assertEquals(150000, $result['transaction']->amount);
    }

    public function test_ai_failure_returns_error(): void
    {
        $mockAiResult = AIParseResult::failure('AI tidak menemukan format transaksi yang valid.');

        $this->instance(AIManager::class, Mockery::mock(AIManager::class, function (MockInterface $mock) use ($mockAiResult) {
            $mock->shouldReceive('parseTransaction')->once()->andReturn($mockAiResult);
        }));

        $this->orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $result = $this->orchestrator->process($this->user, 'halo apa kabar', 'TEL');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('AI Gagal memproses', $result['message']);
        $this->assertBalanceEquals($this->cashWallet, 500000);
    }
}
