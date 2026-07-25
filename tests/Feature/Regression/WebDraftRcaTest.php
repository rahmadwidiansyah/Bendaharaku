<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use App\DTO\AIParseResult;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Models\User;
use App\Services\Chat\ChatTransactionOrchestrator;
use App\Services\AI\AIManager;
use Database\Seeders\TestDataSeeder;
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

        $this->seed(TestDataSeeder::class);

        $this->user = User::where('email', 'test@example.com')->first();
    }

    public function test_beli_makan_20k_web_creates_draft_and_traces_wallet_ids()
    {
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

        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_web_draft']);
        $this->assertNotNull($result['draft']);
        $this->assertEquals('SOURCE', $result['draft']->missing_wallet_side);
        $this->assertTrue($result['draft']->payload['needs_wallet']);
    }
}
