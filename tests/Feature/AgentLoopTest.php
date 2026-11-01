<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Chat\Components\TextComponent;
use App\Chat\DTOs\ChatContext;
use App\DTO\AgentRequest;
use App\DTO\AgentTurn;
use App\Enums\AiProvider;
use App\Enums\ChatIntent;
use App\Enums\ChatPlatform;
use App\Models\AiProviderAccount;
use App\Models\User;
use App\Services\AI\Adapters\DeepSeekAdapter;
use App\Services\AI\Adapters\GeminiAdapter;
use App\Services\AI\Adapters\OpenAIAdapter;
use App\Services\AI\Adapters\OpenAiCompatibleAdapter;
use App\Services\AI\AiProviderFactory;
use App\Services\AI\Assistant\AgentLoop;
use App\Services\AI\Assistant\AssistantConversationPipeline;
use App\Services\AI\Assistant\ConversationHistoryBuilder;
use App\Services\AI\Assistant\ToolRegistry;
use App\Services\AI\Routing\AiRoutingExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentLoopTest extends TestCase
{
    use RefreshDatabase;

    private function account(User $user): AiProviderAccount
    {
        $account = new AiProviderAccount([
            'user_id' => $user->id,
            'provider' => AiProvider::Gemini,
            'alias' => 'Test',
            'api_key' => 'test-key',
            'is_active' => true,
            'is_valid' => true,
        ]);
        $account->id = 1;

        return $account;
    }

    /** @test */
    public function test_question_memanggil_tool_lalu_jawab_final_tanpa_tools(): void
    {
        config()->set('bendaharaku.ai.assistant.max_tool_iterations', 1);

        $user = User::factory()->create();
        $account = $this->account($user);
        $requests = [];

        $adapter = \Mockery::mock(GeminiAdapter::class);
        $adapter->shouldReceive('chatWithTools')->twice()->andReturnUsing(
            function (AgentRequest $request) use (&$requests): AgentTurn {
                $requests[] = $request;

                if (count($requests) === 1) {
                    return new AgentTurn(
                        toolCalls: [[
                            'id' => 'call_1',
                            'name' => 'get_wallets',
                            'arguments' => [],
                        ]],
                        finishReason: 'tool_calls',
                    );
                }

                return new AgentTurn(
                    content: '{"type":"assistant_response","message":"Saldo dompetmu sudah kubaca.","status":"conversation"}',
                    usage: ['prompt' => 10, 'completion' => 5, 'total' => 15],
                    finishReason: 'stop',
                );
            }
        );

        $factory = new AiProviderFactory(
            $adapter,
            app(OpenAIAdapter::class),
            app(DeepSeekAdapter::class),
            app(OpenAiCompatibleAdapter::class),
        );

        $executor = \Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeAgent')->once()->andReturnUsing(
            function (User $user, callable $attempt) use ($account): array {
                $turn = $attempt($account, 'gemini-2.0-flash', AiProvider::Gemini);

                return [
                    'turn' => $turn,
                    'routing' => [
                        'selected_provider' => 'gemini',
                        'selected_model' => 'gemini-2.0-flash',
                        'selected_account_id' => 1,
                        'attempt_count' => 1,
                        'final_status' => 'success',
                        'attempts' => [],
                    ],
                ];
            }
        );

        $loop = new AgentLoop(
            $executor,
            $factory,
            app(ToolRegistry::class),
            app(ConversationHistoryBuilder::class),
        );

        $result = $loop->run($user, 'Berapa saldo dompetku?', 'question', traceId: 'trace-agent');

        $this->assertSame('Saldo dompetmu sudah kubaca.', $result['response']->message);
        $this->assertSame(1, $result['tool_calls']);
        $this->assertSame(1, $result['iterations']);
        $this->assertFalse($result['fallback']);
        $this->assertNotEmpty($requests[0]->tools);
        $this->assertSame([], $requests[1]->tools);
        $this->assertDatabaseHas('ai_tool_logs', [
            'trace_id' => 'trace-agent',
            'tool' => 'get_wallets',
            'status' => 'success',
        ]);
    }

    /** @test */
    public function test_iterasi_lebih_dari_limit_dipaksa_final_tanpa_tools(): void
    {
        config()->set('bendaharaku.ai.assistant.max_tool_iterations', 1);

        $user = User::factory()->create();
        $account = $this->account($user);
        $requests = [];

        $adapter = \Mockery::mock(GeminiAdapter::class);
        $adapter->shouldReceive('chatWithTools')->twice()->andReturnUsing(
            function (AgentRequest $request) use (&$requests): AgentTurn {
                $requests[] = $request;

                if (count($requests) === 1) {
                    // Model tetap keras memanggil tool meski sudah cukup data;
                    // loop harus menutup diri lewat forced-final tanpa tools.
                    return new AgentTurn(
                        toolCalls: [[
                            'id' => 'call_loop',
                            'name' => 'get_wallets',
                            'arguments' => [],
                        ]],
                        finishReason: 'tool_calls',
                    );
                }

                return new AgentTurn(
                    content: '{"type":"assistant_response","message":"Ditutup paksa tanpa tool.","status":"conversation"}',
                    usage: ['prompt' => 5, 'completion' => 2, 'total' => 7],
                    finishReason: 'stop',
                );
            }
        );

        $factory = new AiProviderFactory(
            $adapter,
            app(OpenAIAdapter::class),
            app(DeepSeekAdapter::class),
            app(OpenAiCompatibleAdapter::class),
        );

        $executor = \Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeAgent')->once()->andReturnUsing(
            function (User $user, callable $attempt) use ($account): array {
                $turn = $attempt($account, 'gemini-2.0-flash', AiProvider::Gemini);

                return ['turn' => $turn, 'routing' => ['selected_provider' => 'gemini', 'attempt_count' => 1, 'final_status' => 'success']];
            }
        );

        $loop = new AgentLoop(
            $executor,
            $factory,
            app(ToolRegistry::class),
            app(ConversationHistoryBuilder::class),
        );

        $result = $loop->run($user, 'Berapa dompetku?', 'question', traceId: 'trace-cap');

        $this->assertSame('Ditutup paksa tanpa tool.', $result['response']->message);
        $this->assertSame([], $requests[1]->tools);
    }

    /** @test */
    public function test_tool_error_dikirim_ke_model_lalu_final_tetap_jalan(): void
    {
        $user = User::factory()->create();
        $account = $this->account($user);
        $requests = [];

        $adapter = \Mockery::mock(GeminiAdapter::class);
        $adapter->shouldReceive('chatWithTools')->twice()->andReturnUsing(
            function (AgentRequest $request) use (&$requests): AgentTurn {
                $requests[] = $request;

                if (count($requests) === 1) {
                    // limit 999 di luar validator (max 50) → validation_error.
                    return new AgentTurn(
                        toolCalls: [[
                            'id' => 'call_bad',
                            'name' => 'get_transactions',
                            'arguments' => ['limit' => 999],
                        ]],
                        finishReason: 'tool_calls',
                    );
                }

                return new AgentTurn(
                    content: '{"type":"assistant_response","message":"Parameter transaksinya tidak valid, coba lagi.","status":"conversation"}',
                    usage: ['prompt' => 8, 'completion' => 4, 'total' => 12],
                    finishReason: 'stop',
                );
            }
        );

        $factory = new AiProviderFactory(
            $adapter,
            app(OpenAIAdapter::class),
            app(DeepSeekAdapter::class),
            app(OpenAiCompatibleAdapter::class),
        );

        $executor = \Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeAgent')->once()->andReturnUsing(
            function (User $user, callable $attempt) use ($account): array {
                $turn = $attempt($account, 'gemini-2.0-flash', AiProvider::Gemini);

                return ['turn' => $turn, 'routing' => ['selected_provider' => 'gemini', 'attempt_count' => 1, 'final_status' => 'success']];
            }
        );

        $loop = new AgentLoop(
            $executor,
            $factory,
            app(ToolRegistry::class),
            app(ConversationHistoryBuilder::class),
        );

        $result = $loop->run($user, 'Tampilkan transaksiku', 'question', traceId: 'trace-tool-error');

        $this->assertStringContainsString('tidak valid', $result['response']->message);
        $this->assertDatabaseHas('ai_tool_logs', [
            'trace_id' => 'trace-tool-error',
            'tool' => 'get_transactions',
            'status' => 'validation_error',
        ]);
    }

    /** @test */
    public function test_conversation_tanpa_tools_memakai_generate_text(): void
    {
        $user = User::factory()->create();
        $account = $this->account($user);

        $adapter = \Mockery::mock(GeminiAdapter::class);
        $adapter->shouldReceive('generateText')->once()->andReturn(
            '{"type":"assistant_response","message":"Halo juga! Ada yang bisa kubantu?","status":"conversation"}'
        );

        $factory = new AiProviderFactory(
            $adapter,
            app(OpenAIAdapter::class),
            app(DeepSeekAdapter::class),
            app(OpenAiCompatibleAdapter::class),
        );

        $executor = \Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeText')->once()->andReturnUsing(
            function (User $user, callable $attempt) use ($account): array {
                $text = $attempt($account, 'gemini-2.0-flash', AiProvider::Gemini);

                return [
                    'text' => $text,
                    'routing' => ['selected_provider' => 'gemini', 'selected_model' => 'gemini-2.0-flash'],
                ];
            }
        );

        $loop = new AgentLoop(
            $executor,
            $factory,
            app(ToolRegistry::class),
            app(ConversationHistoryBuilder::class),
        );

        $result = $loop->run($user, 'Halo', 'conversation');

        $this->assertSame('Halo juga! Ada yang bisa kubantu?', $result['response']->message);
        $this->assertSame(0, $result['tool_calls']);
        $this->assertSame(1, $result['iterations']);
    }

    /** @test */
    public function test_pipeline_mengubah_hasil_agent_menjadi_chat_response(): void
    {
        $user = User::factory()->create();
        $account = $this->account($user);

        $adapter = \Mockery::mock(GeminiAdapter::class);
        $adapter->shouldReceive('generateText')->once()->andReturn(
            '{"type":"assistant_response","message":"Halo, aku Ken-Chan.","status":"conversation"}'
        );

        $factory = new AiProviderFactory(
            $adapter,
            app(OpenAIAdapter::class),
            app(DeepSeekAdapter::class),
            app(OpenAiCompatibleAdapter::class),
        );

        $executor = \Mockery::mock(AiRoutingExecutor::class);
        $executor->shouldReceive('executeText')->once()->andReturnUsing(
            function (User $user, callable $attempt) use ($account): array {
                $text = $attempt($account, 'gemini-2.0-flash', AiProvider::Gemini);

                return [
                    'text' => $text,
                    'routing' => [
                        'selected_provider' => 'gemini',
                        'selected_model' => 'gemini-2.0-flash',
                        'attempt_count' => 1,
                        'final_status' => 'success',
                        'attempts' => [],
                    ],
                ];
            }
        );

        $loop = new AgentLoop(
            $executor,
            $factory,
            app(ToolRegistry::class),
            app(ConversationHistoryBuilder::class),
        );
        $pipeline = new AssistantConversationPipeline($loop);
        $context = ChatContext::make(ChatPlatform::Web, '1', 'id');

        $response = $pipeline->handle($user, 'Halo', 'conversation', $context, microtime(true));

        $this->assertTrue($response->success);
        $this->assertSame(ChatIntent::Conversation, $response->intent);
        $this->assertInstanceOf(TextComponent::class, $response->components[0]);
        $this->assertTrue($response->components[0]->literal);
        $this->assertSame('Halo, aku Ken-Chan.', $response->components[0]->translationKey);
        $this->assertSame('gemini', $response->metadata['provider']);
        $this->assertSame('conversation', $response->metadata['intent']);
    }
}
