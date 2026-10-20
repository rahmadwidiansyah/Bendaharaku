<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\ProcessTransactionAction;
use App\DTO\ChatProcessOptions;
use App\Exceptions\AiProviderException;
use App\Models\User;
use App\Services\AI\AiParseLogService;
use App\Services\AI\AiProviderFactory;
use App\Services\AI\Routing\AiRoutingExecutor;
use App\Services\AI\TransactionResolver;
use App\Services\Chat\DraftPayloadBuilder;
use App\Services\Chat\MultiTransactionProcessor;
use App\Services\Wallet\WalletResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MultiFallbackTest extends TestCase
{
    use RefreshDatabase;

    private function processorThrowingPoolExhausted(): MultiTransactionProcessor
    {
        $executor = Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeMulti')
            ->once()
            ->andThrow(new AiProviderException('ai-router', 'Semua kandidat multi-transaksi gagal (8 percobaan).'));

        return new MultiTransactionProcessor(
            app(AiProviderFactory::class),
            app(TransactionResolver::class),
            app(AiParseLogService::class),
            app(WalletResolutionService::class),
            app(ProcessTransactionAction::class),
            app(DraftPayloadBuilder::class),
            $executor,
        );
    }

    public function test_evidence_mode_disables_silent_single_fallback(): void
    {
        $user = User::factory()->create();
        $processor = $this->processorThrowingPoolExhausted();

        $result = $processor->process(
            $user,
            'makan 16rb, pindah 15rb ke dana',
            [],
            [],
            [],
            'WEB',
            '',
            new ChatProcessOptions(allowSingleFallback: false),
        );

        $this->assertFalse($result['success']);
        $this->assertSame('AI_POOL_EXHAUSTED', $result['error_code']);
        $this->assertTrue($result['retryable']);
        $this->assertArrayNotHasKey('__fallback_to_single', $result);
    }

    public function test_chat_mode_keeps_single_fallback_with_marker(): void
    {
        $user = User::factory()->create();
        $processor = $this->processorThrowingPoolExhausted();

        $result = $processor->process($user, 'makan 16rb, pindah 15rb ke dana', [], [], [], 'TEL');

        $this->assertTrue($result['__fallback_to_single']);
        $this->assertTrue($result['multi_fallback']);
        $this->assertSame('pool_exhausted', $result['fallback_reason']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
