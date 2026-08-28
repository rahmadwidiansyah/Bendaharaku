<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\DTO\AIParseResult;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Models\Category;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AI\AIManager;
use App\Services\Chat\ChatTransactionOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ChatTransactionOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Wallet $sourceWallet;

    private Wallet $destWallet;

    private Category $category;

    private ChatTransactionOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup User & Data Master
        $this->user = User::factory()->create(['name' => 'Budi']);

        // HAPUS DATA BAWAAN BOOT AGAR TIDAK DUPLIKAT DENGAN DATA TEST
        $this->user->wallets()->forceDelete();
        $this->user->categories()->forceDelete();

        $expenseType = TransactionType::create(['name' => 'Expense']);

        $this->category = $this->user->categories()->create([
            'category_name' => 'Makan & Minum',
            'keyword' => 'makan',
            'type_id' => $expenseType->id,
        ]);

        $this->sourceWallet = $this->user->wallets()->create([
            'name' => 'Dompet Cash',
            'keyword' => 'cash',
            'balance' => 100000,
            'group_type' => 'Liquid',
        ]);

        $this->destWallet = $this->user->wallets()->create([
            'name' => 'Merchant System',
            'group_type' => 'System',
        ]);

        foreach (['External System', 'Hutang System', 'Piutang System'] as $name) {
            $this->user->wallets()->create(['name' => $name, 'group_type' => 'System']);
        }

        config([
            'bendaharaku.system_wallets.merchant' => 'Merchant System',
            'bendaharaku.system_wallets.external' => 'External System',
            'bendaharaku.system_wallets.debt' => 'Hutang System',
            'bendaharaku.system_wallets.receivable' => 'Piutang System',
        ]);
    }

    /** @test */
    public function test_it_executes_transaction_when_confidence_is_high()
    {
        // Simulasi AI sangat yakin (Confidence: 0.95)
        $parsedTransaction = new ParsedTransaction(
            amount: 15000,
            transactionType: TransactionIntent::Expense,
            category: 'makan',
            sourceWallet: 'cash',
            isCleared: true
        );

        $mockAiResult = new AIParseResult(true, 0.95, null, $parsedTransaction);

        // Mock AIManager agar mengembalikan hasil di atas tanpa nembak API asli
        $this->instance(
            AIManager::class,
            Mockery::mock(AIManager::class, function (MockInterface $mock) use ($mockAiResult) {
                $mock->shouldReceive('parseTransaction')->once()->andReturn($mockAiResult);
            })
        );

        $this->orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $result = $this->orchestrator->process($this->user, 'beli kopi 15rb cash', 'TEL');

        // Assert Transaksi Sukses & Tereksekusi
        $this->assertTrue($result['success']);
        $this->assertTrue($result['transaction']->is_cleared);
        $this->assertEquals(15000, $result['transaction']->amount);

        // Assert Saldo Berkurang (100.000 - 15.000 = 85.000)
        $this->assertEquals(85000, $this->sourceWallet->fresh()->balance);
    }

    /** @test */
    public function test_it_forces_draft_when_confidence_is_low()
    {
        // Simulasi AI Ragu (Confidence: 0.45)
        $parsedTransaction = new ParsedTransaction(
            amount: 50000,
            transactionType: TransactionIntent::Expense,
            category: 'makan',
            sourceWallet: 'cash',
            isCleared: false // Dipaksa false oleh ValidationService pada runtime sesungguhnya
        );

        $mockAiResult = new AIParseResult(true, 0.45, null, $parsedTransaction);

        $this->instance(
            AIManager::class,
            Mockery::mock(AIManager::class, function (MockInterface $mock) use ($mockAiResult) {
                $mock->shouldReceive('parseTransaction')->once()->andReturn($mockAiResult);
            })
        );

        $this->orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $result = $this->orchestrator->process($this->user, 'kayaknya kemarin beli makan 50rb', 'TEL');

        // Assert Transaksi Disimpan sebagai Draft
        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_web_draft']);
        $this->assertNotNull($result['draft']);
        $this->assertEquals('pending', $result['draft']->status);
        $this->assertDatabaseHas('transaction_drafts', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('transaction_logs', 0);

        // Assert Saldo AMAN (Tetap 100.000)
        $this->assertEquals(100000, $this->sourceWallet->fresh()->balance);
    }

    /** @test */
    public function test_it_rejects_transaction_when_ai_extraction_fails()
    {
        // Simulasi AI gagal mengenali instruksi
        $mockAiResult = AIParseResult::failure('AI tidak menemukan format transaksi yang valid.');

        $this->instance(
            AIManager::class,
            Mockery::mock(AIManager::class, function (MockInterface $mock) use ($mockAiResult) {
                $mock->shouldReceive('parseTransaction')->once()->andReturn($mockAiResult);
            })
        );

        $this->orchestrator = $this->app->make(ChatTransactionOrchestrator::class);
        $result = $this->orchestrator->process($this->user, 'halo apa kabar bot', 'TEL');

        // Assert Transaksi Gagal
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('AI Gagal memproses', $result['message']);

        // Assert Saldo Tidak Berubah
        $this->assertEquals(100000, $this->sourceWallet->fresh()->balance);
    }
}
