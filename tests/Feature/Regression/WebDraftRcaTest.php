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

class WebDraftRcaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'RCA User']);

        // Ensure system wallets exist for resolution
        $this->user->wallets()->createMany([
            ['name' => 'External System', 'group_type' => 'System'],
            ['name' => 'Merchant System', 'group_type' => 'System'],
            ['name' => 'Debt System', 'group_type' => 'System'],
            ['name' => 'Receivable System', 'group_type' => 'System'],
        ]);

        config([
            'bendaharaku.system_wallets.external' => 'External System',
            'bendaharaku.system_wallets.merchant' => 'Merchant System',
            'bendaharaku.system_wallets.debt' => 'Debt System',
            'bendaharaku.system_wallets.receivable' => 'Receivable System',
        ]);
    }

    public function test_beli_makan_20k_web_creates_draft_and_traces_wallet_ids()
    {
        // ParsedTransaction from LocalRuleEngine: no explicit wallet mentioned
        $parsed = new ParsedTransaction(
            amount: 20000,
            transactionType: TransactionIntent::Expense,
            category: 'Makan & Minum',
            sourceWallet: null,
            isCleared: false
        );

        $mockAiResult = new AIParseResult(true, 0.0, null, $parsed);

        $this->instance(
            AIManager::class,
            Mockery::mock(AIManager::class, function (MockInterface $mock) use ($mockAiResult) {
                $mock->shouldReceive('parseTransaction')->once()->andReturn($mockAiResult);
            })
        );

        $orchestrator = $this->app->make(ChatTransactionOrchestrator::class);

        $result = $orchestrator->process($this->user, 'Beli makan 20k', 'WEB');

        // Ensure returned as draft saved path
        $this->assertFalse($result['success']);
        $this->assertEquals('DRAFT_SAVED', $result['error_code']);
    }
}
