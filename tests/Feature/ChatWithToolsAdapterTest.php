<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTO\AgentRequest;
use App\Enums\AiProvider;
use App\Models\AiModelCatalog;
use App\Models\AiProviderAccount;
use App\Models\User;
use App\Services\AI\Adapters\GeminiAdapter;
use App\Services\AI\Adapters\OpenAIAdapter;
use App\Services\AI\Routing\ModelResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatWithToolsAdapterTest extends TestCase
{
    use RefreshDatabase;

    private function tools(): array
    {
        return [[
            'name' => 'get_wallets',
            'description' => 'Ambil dompet pengguna.',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
                'required' => [],
            ],
        ]];
    }

    /** @test */
    public function test_gemini_memakai_system_instruction_dan_function_declarations(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'functionCall' => ['name' => 'get_wallets', 'args' => []],
                    ]]],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => [
                    'promptTokenCount' => 10,
                    'candidatesTokenCount' => 5,
                    'totalTokenCount' => 15,
                ],
            ], 200),
        ]);

        $request = new AgentRequest(
            provider: AiProvider::Gemini,
            model: 'gemini-2.0-flash',
            apiKey: 'test-key',
            messages: [
                ['role' => 'system', 'content' => 'ATURAN INTI'],
                ['role' => 'user', 'content' => 'Berapa saldo dompetku?'],
            ],
            tools: $this->tools(),
        );

        $turn = app(GeminiAdapter::class)->chatWithTools($request);

        $this->assertTrue($turn->hasToolCalls());
        $this->assertNull($turn->content);
        $this->assertSame('get_wallets', $turn->toolCalls[0]['name']);
        $this->assertSame(15, $turn->usage['total']);

        Http::assertSent(function (Request $sent): bool {
            $data = $sent->data();

            return str_contains($sent->url(), ':generateContent')
                && $data['systemInstruction']['parts'][0]['text'] === 'ATURAN INTI'
                && $data['contents'][0]['role'] === 'user'
                && $data['tools'][0]['functionDeclarations'][0]['name'] === 'get_wallets';
        });
    }

    /** @test */
    public function test_gemini_mempertahankan_id_dan_thought_signature_pada_riwayat_tool(): void
    {
        $signature = 'sig-test-base64==';
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'functionCall' => [
                            'name' => 'get_wallets',
                            'args' => [],
                            'id' => 'call_122760',
                        ],
                        'thoughtSignature' => $signature,
                    ]]],
                    'finishReason' => 'STOP',
                ]],
            ], 200),
        ]);

        $first = new AgentRequest(
            provider: AiProvider::Gemini,
            model: 'gemini-3.6-flash',
            apiKey: 'test-key',
            messages: [
                ['role' => 'system', 'content' => 'ATURAN INTI'],
                ['role' => 'user', 'content' => 'Berapa saldo?'],
            ],
            tools: $this->tools(),
        );

        $turn = app(GeminiAdapter::class)->chatWithTools($first);

        // Id asli Gemini dan thoughtSignature ikut diekstrak.
        $this->assertSame('call_122760', $turn->toolCalls[0]['id']);
        $this->assertSame('call_122760', $turn->toolCalls[0]['provider_id']);
        $this->assertSame($signature, $turn->toolCalls[0]['thought_signature']);

        // Kirim ulang riwayat: assistant(functionCall) + tool(functionResponse).
        $second = new AgentRequest(
            provider: AiProvider::Gemini,
            model: 'gemini-3.6-flash',
            apiKey: 'test-key',
            messages: [
                ['role' => 'system', 'content' => 'ATURAN INTI'],
                ['role' => 'user', 'content' => 'Berapa saldo?'],
                ['role' => 'assistant', 'content' => null, 'tool_calls' => $turn->toolCalls],
                [
                    'role' => 'tool',
                    'tool_call_id' => 'call_122760',
                    'name' => 'get_wallets',
                    'content' => json_encode(['ok' => true, 'data' => []]),
                ],
            ],
            tools: $this->tools(),
        );

        app(GeminiAdapter::class)->chatWithTools($second);

        // functionCall di-echo dengan id native + thoughtSignature di level part.
        // system masuk systemInstruction → contents: [user, model, functionResponse].
        Http::assertSent(function (Request $sent) use ($signature): bool {
            $data = $sent->data();
            $modelTurn = $data['contents'][1] ?? null;

            return $modelTurn !== null
                && ($modelTurn['role'] ?? '') === 'model'
                && ($modelTurn['parts'][0]['functionCall']['id'] ?? null) === 'call_122760'
                && ($modelTurn['parts'][0]['thoughtSignature'] ?? null) === $signature
                && ($data['contents'][2]['parts'][0]['functionResponse']['name'] ?? null) === 'get_wallets';
        });
    }

    /** @test */
    public function test_gemini_tanpa_id_dan_signature_tetap_kompatibel_ke_belakang(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'functionCall' => ['name' => 'get_wallets', 'args' => []],
                    ]]],
                    'finishReason' => 'STOP',
                ]],
            ], 200),
        ]);

        $first = new AgentRequest(
            provider: AiProvider::Gemini,
            model: 'gemini-2.0-flash',
            apiKey: 'test-key',
            messages: [
                ['role' => 'system', 'content' => 'ATURAN INTI'],
                ['role' => 'user', 'content' => 'Berapa saldo?'],
            ],
            tools: $this->tools(),
        );

        $turn = app(GeminiAdapter::class)->chatWithTools($first);

        // Id sintetis, tanpa signature dan tanpa id native.
        $this->assertStringStartsWith('call_', $turn->toolCalls[0]['id']);
        $this->assertNull($turn->toolCalls[0]['provider_id']);
        $this->assertNull($turn->toolCalls[0]['thought_signature']);

        $second = new AgentRequest(
            provider: AiProvider::Gemini,
            model: 'gemini-2.0-flash',
            apiKey: 'test-key',
            messages: [
                ['role' => 'system', 'content' => 'ATURAN INTI'],
                ['role' => 'user', 'content' => 'Berapa saldo?'],
                ['role' => 'assistant', 'content' => null, 'tool_calls' => $turn->toolCalls],
                [
                    'role' => 'tool',
                    'tool_call_id' => $turn->toolCalls[0]['id'],
                    'name' => 'get_wallets',
                    'content' => json_encode(['ok' => true, 'data' => []]),
                ],
            ],
            tools: $this->tools(),
        );

        app(GeminiAdapter::class)->chatWithTools($second);

        // Tanpa id/signature dari provider: body rebuild tidak menyertakan keduanya.
        // system masuk systemInstruction → contents: [user, model, functionResponse].
        Http::assertSent(function (Request $sent): bool {
            $data = $sent->data();
            $modelTurn = $data['contents'][1] ?? null;

            return $modelTurn !== null
                && ($modelTurn['role'] ?? '') === 'model'
                && ! isset($modelTurn['parts'][0]['functionCall']['id'])
                && ! isset($modelTurn['parts'][0]['thoughtSignature']);
        });
    }

    /** @test */
    public function test_openai_memakai_role_tool_dan_tidak_mengirim_response_format(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call_abc',
                            'type' => 'function',
                            'function' => [
                                'name' => 'get_wallets',
                                'arguments' => '{"limit":5}',
                            ],
                        ]],
                    ],
                    'finish_reason' => 'tool_calls',
                ]],
                'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 4, 'total_tokens' => 16],
            ], 200),
        ]);

        $request = new AgentRequest(
            provider: AiProvider::OpenAI,
            model: 'gpt-4o-mini',
            apiKey: 'test-key',
            messages: [
                ['role' => 'system', 'content' => 'ATURAN INTI'],
                ['role' => 'user', 'content' => 'Berapa saldo dompetku?'],
                [
                    'role' => 'assistant',
                    'content' => null,
                    'tool_calls' => [['id' => 'call_abc', 'name' => 'get_wallets', 'arguments' => []]],
                ],
                [
                    'role' => 'tool',
                    'tool_call_id' => 'call_abc',
                    'name' => 'get_wallets',
                    'content' => '{"ok":true}',
                ],
            ],
            tools: $this->tools(),
        );

        $turn = app(OpenAIAdapter::class)->chatWithTools($request);

        $this->assertSame('call_abc', $turn->toolCalls[0]['id']);
        $this->assertSame(['limit' => 5], $turn->toolCalls[0]['arguments']);
        $this->assertSame('tool_calls', $turn->finishReason);

        Http::assertSent(function (Request $sent): bool {
            $data = $sent->data();

            return ! array_key_exists('response_format', $data)
                && $data['tools'][0]['type'] === 'function'
                && $data['messages'][2]['tool_calls'][0]['function']['arguments'] === '[]'
                && $data['messages'][3]['role'] === 'tool'
                && $data['messages'][3]['tool_call_id'] === 'call_abc';
        });
    }

    /** @test */
    public function test_semua_provider_aktif_mendukung_tools(): void
    {
        foreach (AiProvider::cases() as $provider) {
            $this->assertTrue($provider->supportsTools(), $provider->value);
        }
    }

    /** @test */
    public function test_model_resolver_melewati_akun_dengan_disable_tools(): void
    {
        config()->set('bendaharaku.ai.openai_compatible.api_key', '');
        config()->set('bendaharaku.ai.openai_compatible.base_url', '');

        $user = User::factory()->create();

        $account = AiProviderAccount::create([
            'user_id' => $user->id,
            'provider' => AiProvider::Gemini,
            'alias' => 'Tanpa Tools',
            'api_key' => 'test-key',
            'is_active' => true,
            'is_valid' => true,
            'priority' => 1,
            'health_status' => 'healthy',
            'metadata' => ['disable_tools' => true],
        ]);

        AiModelCatalog::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'provider' => AiProvider::Gemini,
            'model_id' => 'gemini-2.0-flash',
            'display_name' => 'Gemini 2.0 Flash',
            'supports_generate' => true,
            'category' => 'text_generation',
            'free_tier' => 'free',
            'is_eligible' => true,
            'is_enabled' => true,
            'discovery_source' => 'seed',
        ]);

        $resolved = app(ModelResolver::class)->resolve($user, null, true);

        $this->assertSame([], $resolved['candidates']);
        $this->assertSame('none_no_usable_account', $resolved['method']);
    }
}
