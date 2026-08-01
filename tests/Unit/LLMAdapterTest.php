<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Services\AI\Adapters\DeepSeekAdapter;
use App\Services\AI\Adapters\GeminiAdapter;
use App\Services\AI\Adapters\OpenAIAdapter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LLMAdapterTest extends TestCase
{
    private OpenAIAdapter $openAI;

    private DeepSeekAdapter $deepSeek;

    private GeminiAdapter $gemini;

    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
        $this->openAI = new OpenAIAdapter;
        $this->deepSeek = new DeepSeekAdapter;
        $this->gemini = new GeminiAdapter;
    }

    public function test_openai_parses_single_transaction(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'amount' => 50000,
                    'category' => 'Makanan',
                    'sourceWallet' => 'Cash',
                    'transactionType' => 'expense',
                    'confidence' => 0.92,
                    'notes' => 'beli nasi goreng',
                ])]]],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 50, 'total_tokens' => 150],
            ]),
        ]);

        $result = $this->openAI->parseTransaction('test prompt', 'fake-key', 'gpt-4o', 'beli nasi goreng');

        $this->assertTrue($result->success);
        $this->assertSame(50000.0, $result->transaction?->amount);
        $this->assertSame('Makanan', $result->transaction?->category);
        $this->assertSame('Cash', $result->transaction?->sourceWallet);
        $this->assertSame('openai', $result->provider);
        $this->assertSame(100, $result->usage['prompt']);
        $this->assertSame(150, $result->usage['total']);
    }

    public function test_openai_parses_multi_transaction(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'transactions' => [
                        ['amount' => 20000, 'category' => 'Makanan', 'confidence' => 0.9],
                        ['amount' => 15000, 'category' => 'Transport', 'confidence' => 0.85],
                    ],
                ])]]],
                'usage' => ['prompt_tokens' => 80, 'completion_tokens' => 60, 'total_tokens' => 140],
            ]),
        ]);

        $result = $this->openAI->parseMultiTransaction('test prompt', 'fake-key', 'gpt-4o');

        $this->assertTrue($result->success);
        $this->assertCount(2, $result->transactions);
        $this->assertSame(20000.0, $result->transactions[0]->amount);
        $this->assertSame('Makanan', $result->transactions[0]->category);
        $this->assertSame(0.875, $result->confidence);
    }

    public function test_deepseek_parses_single_transaction(): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'amount' => 75000,
                    'category' => 'Transport',
                    'sourceWallet' => 'BCA',
                    'transactionType' => 'expense',
                ])]]],
                'usage' => ['prompt_tokens' => 90, 'completion_tokens' => 40, 'total_tokens' => 130],
            ]),
        ]);

        $result = $this->deepSeek->parseTransaction('test prompt', 'fake-key', 'deepseek-chat', 'grab 75rb');

        $this->assertTrue($result->success);
        $this->assertSame(75000.0, $result->transaction?->amount);
        $this->assertSame('Transport', $result->transaction?->category);
        $this->assertSame('deepseek', $result->provider);
    }

    public function test_deepseek_handles_non_transaction_response(): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'is_transaction' => false,
                    'reply_message' => 'Maaf, saya tidak mengerti.',
                ])]]],
                'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 10, 'total_tokens' => 60],
            ]),
        ]);

        $result = $this->deepSeek->parseTransaction('test prompt', 'fake-key', 'deepseek-chat');

        $this->assertFalse($result->success);
        $this->assertSame('Maaf, saya tidak mengerti.', $result->error);
    }

    public function test_gemini_parses_single_transaction(): void
    {
        $rawText = json_encode([
            'amount' => 100000,
            'category' => 'Belanja',
            'sourceWallet' => 'Cash',
            'transactionType' => 'expense',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => $rawText]]]],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 70,
                    'candidatesTokenCount' => 30,
                    'totalTokenCount' => 100,
                ],
            ]),
        ]);

        $result = $this->gemini->parseTransaction('test prompt', 'fake-key', 'gemini-2.0-flash', 'belanja 100rb');

        $this->assertTrue($result->success);
        $this->assertSame(100000.0, $result->transaction?->amount);
        $this->assertSame('Belanja', $result->transaction?->category);
        $this->assertSame('gemini', $result->provider);
        $this->assertSame(70, $result->usage['prompt']);
        $this->assertSame(30, $result->usage['completion']);
        $this->assertSame(100, $result->usage['total']);
    }

    public function test_gemini_parses_successful_response_without_usage_metadata(): void
    {
        $rawText = json_encode([
            'amount' => 120000,
            'category' => 'Belanja',
            'sourceWallet' => 'Cash',
            'transactionType' => 'expense',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => $rawText]]]],
                ],
            ]),
        ]);

        $result = $this->gemini->parseTransaction('test prompt', 'fake-key', 'gemini-2.0-flash', 'belanja 120rb');

        $this->assertTrue($result->success);
        $this->assertSame(120000.0, $result->transaction?->amount);
        $this->assertSame(['prompt' => 0, 'completion' => 0, 'total' => 0], $result->usage);
    }

    public function test_gemini_parses_usage_metadata_without_total_token_count(): void
    {
        $rawText = json_encode([
            'amount' => 45000,
            'category' => 'Makanan',
            'sourceWallet' => 'Cash',
            'transactionType' => 'expense',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => $rawText]]]],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 25,
                    'candidatesTokenCount' => 15,
                ],
            ]),
        ]);

        $result = $this->gemini->parseTransaction('test prompt', 'fake-key', 'gemini-2.0-flash', 'makan 45rb');

        $this->assertTrue($result->success);
        $this->assertSame(25, $result->usage['prompt']);
        $this->assertSame(15, $result->usage['completion']);
        $this->assertSame(40, $result->usage['total']);
    }

    public function test_gemini_parses_multi_transaction(): void
    {
        $rawText = json_encode([
            'transactions' => [
                ['amount' => 50000, 'category' => 'Makanan', 'confidence' => 0.95],
            ],
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => $rawText]]]],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 60,
                    'candidatesTokenCount' => 20,
                    'totalTokenCount' => 80,
                ],
            ]),
        ]);

        $result = $this->gemini->parseMultiTransaction('test prompt', 'fake-key', 'gemini-2.0-flash');

        $this->assertTrue($result->success);
        $this->assertCount(1, $result->transactions);
        $this->assertSame(50000.0, $result->transactions[0]->amount);
    }

    public function test_gemini_error_response_throws_provider_exception(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'Invalid request',
                    'status' => 'INVALID_ARGUMENT',
                ],
            ], 400),
        ]);

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('HTTP 400');

        $this->gemini->parseTransaction('test prompt', 'fake-key', 'gemini-2.0-flash');
    }

    public function test_openai_rejects_invalid_response(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'not json']]],
            ]),
        ]);

        $this->expectException(AiProviderException::class);
        $this->openAI->parseTransaction('test prompt', 'fake-key', 'gpt-4o');
    }

    public function test_all_adapters_handle_rate_limit(): void
    {
        Http::fake([
            '*' => Http::response([], 429),
        ]);

        $this->expectException(AiRateLimitException::class);
        $this->openAI->parseTransaction('test', 'key', 'model');
    }

    public function test_all_adapters_handle_timeout_status(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        $this->expectException(AiTimeoutException::class);
        $this->deepSeek->parseTransaction('test', 'key', 'model');
    }

    public function test_all_adapters_handle_auth_error(): void
    {
        Http::fake([
            '*' => Http::response([], 401),
        ]);

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('API Key tidak valid');
        $this->openAI->parseTransaction('test', 'key', 'model');
    }

    public function test_gemini_handles_auth_error(): void
    {
        Http::fake([
            '*' => Http::response([], 401),
        ]);

        try {
            $this->gemini->parseTransaction('test', 'key', 'model');
            $this->fail('Expected AiProviderException was not thrown');
        } catch (AiProviderException $e) {
            $this->assertStringContainsString('401', $e->getMessage());
        }
    }

    public function test_openai_and_deepseek_handle_error_statuses(): void
    {
        Http::fake([
            '*' => Http::response([], 401),
        ]);

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('API Key tidak valid');
        $this->deepSeek->parseTransaction('test', 'key', 'model');
    }
}
