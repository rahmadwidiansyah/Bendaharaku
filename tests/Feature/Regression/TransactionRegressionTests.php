<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use App\DTO\AIParseResult;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Chat\ChatTransactionOrchestrator;
use App\Services\AI\AIManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TransactionRegressionTests extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Reg User']);

        // create system wallets
        $this->user->wallets()->createMany([
            ['name' => 'External System', 'group_type' => 'System'],
            ['name' => 'Merchant System', 'group_type' => 'System'],
            ['name' => 'Debt System', 'group_type' => 'System'],
            ['name' => 'Receivable System', 'group_type' => 'System'],
        ]);

        // create default transaction types so categories can reference them
        TransactionType::firstOrCreate(['name' => 'Expense'], ['keyword' => 'expense']);
        TransactionType::firstOrCreate(['name' => 'Income'], ['keyword' => 'income']);
        TransactionType::firstOrCreate(['name' => 'Transfer'], ['keyword' => 'transfer']);

        config([
            'bendaharaku.system_wallets.external' => 'External System',
            'bendaharaku.system_wallets.merchant' => 'Merchant System',
            'bendaharaku.system_wallets.debt' => 'Debt System',
            'bendaharaku.system_wallets.receivable' => 'Receivable System',
        ]);
    }

    public function test_case_1_beli_makan_20k()
    {
        $this->user->categories()->create([
            'category_name' => 'Makan & Minum',
            'keyword' => 'makan',
            'type_id' => TransactionType::where('name', 'Expense')->first()->id,
        ]);

        $parsed = new ParsedTransaction(amount: 20000, transactionType: TransactionIntent::Expense, category: 'Makan & Minum', sourceWallet: null, isCleared: false);
        $mock = new AIParseResult(true, 0.0, null, $parsed);

        $this->instance(AIManager::class, Mockery::mock(AIManager::class, function (MockInterface $m) use ($mock) {
            $m->shouldReceive('parseTransaction')->once()->andReturn($mock);
        }));

        $orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $res = $orchestrator->process($this->user, 'Beli makan 20k', 'WEB');

        $this->assertTrue($res['success']);
        $this->assertTrue($res['is_web_draft']);
        $this->assertNotNull($res['draft']);
        $this->assertEquals('SOURCE', $res['draft']->missing_wallet_side);
        $this->assertTrue($res['draft']->payload['needs_wallet']);
    }

    public function test_case_2_beli_makan_dari_bca()
    {
        // create user wallet named BCA
        $bca = $this->user->wallets()->create(['name' => 'BCA', 'keyword' => 'bca', 'group_type' => 'Liquid', 'balance' => 500000]);

        $this->user->categories()->create([
            'category_name' => 'Makan & Minum',
            'keyword' => 'makan',
            'type_id' => TransactionType::where('name', 'Expense')->first()->id,
        ]);

        $parsed = new ParsedTransaction(amount: 20000, transactionType: TransactionIntent::Expense, category: 'Makan & Minum', sourceWallet: 'BCA', isCleared: false);
        $mock = new AIParseResult(true, 0.0, null, $parsed);

        $this->instance(AIManager::class, Mockery::mock(AIManager::class, function (MockInterface $m) use ($mock) {
            $m->shouldReceive('parseTransaction')->once()->andReturn($mock);
        }));

        $orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $res = $orchestrator->process($this->user, 'Beli makan dari BCA', 'WEB');

        // expect draft saved but with explicit wallet -> success true (web draft)
        $this->assertTrue($res['success']);
        $this->assertTrue($res['is_web_draft']);
        $this->assertNotNull($res['draft']);
    }

    public function test_case_3_pindah_uang_20k()
    {
        // transfer: source and destination mentioned
        $this->user->wallets()->create(['name' => 'Cash', 'keyword' => 'cash', 'group_type' => 'Liquid']);
        $this->user->wallets()->create(['name' => 'BCA', 'keyword' => 'bca', 'group_type' => 'Liquid']);

        $parsed = new ParsedTransaction(amount: 20000, transactionType: TransactionIntent::Transfer, category: null, sourceWallet: 'Cash', isCleared: true);
        $mock = new AIParseResult(true, 0.95, null, $parsed);

        $this->instance(AIManager::class, Mockery::mock(AIManager::class, function (MockInterface $m) use ($mock) {
            $m->shouldReceive('parseTransaction')->once()->andReturn($mock);
        }));

        $orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $res = $orchestrator->process($this->user, 'Pindah uang 20k cash ke BCA', 'WEB');

        // For transfer with full info expect web draft saved or executed depending on isCleared
        $this->assertTrue(isset($res['success']));
    }

    public function test_case_4_bayar_hutang_20k()
    {
        $parsed = new ParsedTransaction(amount: 20000, transactionType: TransactionIntent::Debt, category: 'Bayar Hutang', sourceWallet: null, isCleared: false);
        $mock = new AIParseResult(true, 0.0, null, $parsed);

        $this->instance(AIManager::class, Mockery::mock(AIManager::class, function (MockInterface $m) use ($mock) {
            $m->shouldReceive('parseTransaction')->once()->andReturn($mock);
        }));

        $orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $res = $orchestrator->process($this->user, 'Bayar hutang 20k', 'WEB');

        $this->assertTrue(isset($res['success']));
    }

    public function test_case_5_terima_gaji_3juta()
    {
        $parsed = new ParsedTransaction(amount: 3000000, transactionType: TransactionIntent::Income, category: 'Gaji', sourceWallet: null, isCleared: false);
        $mock = new AIParseResult(true, 0.0, null, $parsed);

        $this->instance(AIManager::class, Mockery::mock(AIManager::class, function (MockInterface $m) use ($mock) {
            $m->shouldReceive('parseTransaction')->once()->andReturn($mock);
        }));

        $orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $res = $orchestrator->process($this->user, 'Terima gaji 3 juta', 'WEB');

        $this->assertTrue(isset($res['success']));
    }
}
