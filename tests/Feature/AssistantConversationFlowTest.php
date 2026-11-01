<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Chat\ChatApplicationService;
use App\Chat\Components\TextComponent;
use App\Chat\DTOs\ChatContext;
use App\Chat\DTOs\ChatRequest;
use App\Enums\ChatIntent;
use App\Enums\ChatPlatform;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\AI\Routing\AiRoutingExecutor;
use App\Services\Chat\ChatTransactionOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssistantConversationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function request(User $user, string $text): ChatRequest
    {
        return ChatRequest::make(
            rawMessage: $text,
            user: $user,
            context: ChatContext::make(ChatPlatform::Web, '1', 'id'),
        );
    }

    /** @test */
    public function test_pesan_umum_tidak_masuk_orchestrator_dan_dijawab_pipeline(): void
    {
        config()->set('bendaharaku.ai.assistant.intent_llm', false);

        $user = User::factory()->create();

        $orchestrator = \Mockery::mock(ChatTransactionOrchestrator::class);
        $orchestrator->shouldReceive('process')->never();
        $this->app->instance(ChatTransactionOrchestrator::class, $orchestrator);

        $executor = \Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeText')->once()->andReturn([
            'text' => '{"type":"assistant_response","message":"Halo! Ada yang bisa kubantu?","status":"conversation"}',
            'routing' => [
                'selected_provider' => 'gemini',
                'selected_model' => 'gemini-2.0-flash',
                'attempt_count' => 1,
                'final_status' => 'success',
                'attempts' => [],
            ],
        ]);
        $this->app->instance(AiRoutingExecutor::class, $executor);

        $response = app(ChatApplicationService::class)->handleMessage($this->request($user, 'Halo'));

        $this->assertTrue($response->success);
        $this->assertSame(ChatIntent::Conversation, $response->intent);
        $this->assertInstanceOf(TextComponent::class, $response->components[0]);
        $this->assertTrue($response->components[0]->literal);
        $this->assertSame('Halo! Ada yang bisa kubantu?', $response->components[0]->translationKey);
        $this->assertSame('conversation', $response->metadata['intent']);
    }

    /** @test */
    public function test_transaksi_tetap_lewat_orchestrator_dan_mendapat_pesan_natural(): void
    {
        config()->set('bendaharaku.ai.assistant.natural_reply', true);

        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user);

        $orchestrator = \Mockery::mock(ChatTransactionOrchestrator::class);
        $orchestrator->shouldReceive('process')->once()->andReturn([
            'success' => true,
            'transaction' => $transaction,
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'confidence' => 0.95,
            'usage' => ['total' => 20],
            'routing' => [
                'selected_provider' => 'gemini',
                'selected_model' => 'gemini-2.0-flash',
                'attempt_count' => 1,
                'final_status' => 'success',
                'attempts' => [],
            ],
        ]);
        $this->app->instance(ChatTransactionOrchestrator::class, $orchestrator);

        $executor = \Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeText')->once()->andReturn([
            'text' => '{"type":"assistant_response","message":"Siap, pengeluaranmu sudah kucatat.","status":"success"}',
            'routing' => ['selected_provider' => 'gemini', 'selected_model' => 'gemini-2.0-flash'],
        ]);
        $this->app->instance(AiRoutingExecutor::class, $executor);

        $response = app(ChatApplicationService::class)->handleMessage($this->request($user, 'Beli kopi 25rb'));

        $this->assertTrue($response->success);
        $this->assertSame(ChatIntent::SingleTransaction, $response->intent);
        $this->assertInstanceOf(TextComponent::class, $response->components[0]);
        $this->assertTrue($response->components[0]->literal);
        $this->assertSame('Siap, pengeluaranmu sudah kucatat.', $response->components[0]->translationKey);
    }

    private function makeTransaction(User $user): TransactionLog
    {
        $expenseType = TransactionType::where('name', 'Expense')->firstOrFail();
        $category = $user->categories()->where('type_id', $expenseType->id)->firstOrFail();
        $wallet = $user->wallets()->where('group_type', 'Liquid')->firstOrFail();

        return TransactionLog::create([
            'reference_number' => 'TEST-'.Str::uuid(),
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'type_id' => $expenseType->id,
            'category_id' => $category->id,
            'source_wallet_id' => $wallet->id,
            'destination_wallet_id' => null,
            'amount' => 25000,
            'balance_before' => 100000,
            'balance_after' => 75000,
            'subject' => 'Kopi',
            'notes' => null,
            'is_cleared' => true,
        ]);
    }
}
