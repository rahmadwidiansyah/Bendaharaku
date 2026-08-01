<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AI\Context\AIContext;
use App\Services\AI\Prompt\PromptRenderer;
use PHPUnit\Framework\TestCase;

class PromptRendererTest extends TestCase
{
    private PromptRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new PromptRenderer;
    }

    public function test_render_single_returns_valid_json(): void
    {
        $context = $this->makeContext();

        $result = $this->renderer->renderSingle($context);

        $this->assertJson($result);
        $payload = json_decode($result, true);
        $this->assertArrayHasKey('instruction', $payload);
        $this->assertArrayHasKey('text', $payload);
        $this->assertArrayHasKey('available_wallets', $payload);
        $this->assertArrayHasKey('available_categories', $payload);
    }

    public function test_render_single_includes_instruction(): void
    {
        $context = $this->makeContext();

        $result = $this->renderer->renderSingle($context);

        $payload = json_decode($result, true);
        $this->assertStringContainsString('Extract financial transaction', $payload['instruction']);
        $this->assertStringContainsString('sourceWallet=null', $payload['instruction']);
        $this->assertStringContainsString('Amount shorthand', $payload['instruction']);
    }

    public function test_render_single_includes_user_text(): void
    {
        $context = $this->makeContext(['userInput' => 'beli bakso 20rb']);

        $result = $this->renderer->renderSingle($context);

        $payload = json_decode($result, true);
        $this->assertSame('beli bakso 20rb', $payload['text']);
    }

    public function test_render_single_includes_historical_patterns_when_memories_exist(): void
    {
        $context = $this->makeContext([
            'memories' => [
                ['keyword' => 'bakso', 'category' => 'Makanan', 'effective_weight' => 3.0],
            ],
        ]);

        $result = $this->renderer->renderSingle($context);

        $payload = json_decode($result, true);
        $this->assertArrayHasKey('user_historical_patterns', $payload);
        $this->assertCount(1, $payload['user_historical_patterns']);
        $this->assertSame('bakso', $payload['user_historical_patterns'][0]['keyword']);
        $this->assertSame('Makanan', $payload['user_historical_patterns'][0]['target_category']);
    }

    public function test_render_single_skips_historical_patterns_when_no_memories(): void
    {
        $context = $this->makeContext(['memories' => []]);

        $result = $this->renderer->renderSingle($context);

        $payload = json_decode($result, true);
        $this->assertArrayNotHasKey('user_historical_patterns', $payload);
    }

    public function test_render_multi_returns_valid_json(): void
    {
        $context = $this->makeContext();

        $result = $this->renderer->renderMulti($context);

        $this->assertJson($result);
        $payload = json_decode($result, true);
        $this->assertArrayHasKey('instruction', $payload);
        $this->assertArrayHasKey('available_wallets', $payload);
        $this->assertArrayHasKey('available_categories', $payload);
        $this->assertArrayHasKey('wallet_keyword_aliases', $payload);
        $this->assertArrayHasKey('response_format', $payload);
    }

    public function test_render_multi_includes_keyword_aliases(): void
    {
        $context = $this->makeContext([
            'aliases' => ['spay' => 'ShopeePay', 'makan' => 'Makanan'],
        ]);

        $result = $this->renderer->renderMulti($context);

        $payload = json_decode($result, true);
        $this->assertSame('ShopeePay', $payload['wallet_keyword_aliases']['spay']);
        $this->assertSame('Makanan', $payload['category_keyword_aliases']['makan']);
    }

    public function test_render_multi_includes_historical_patterns(): void
    {
        $context = $this->makeContext([
            'memories' => [
                ['keyword' => 'bensin', 'category' => 'Transport', 'effective_weight' => 2.0],
            ],
        ]);

        $result = $this->renderer->renderMulti($context);

        $payload = json_decode($result, true);
        $this->assertArrayHasKey('historical_patterns', $payload);
        $this->assertCount(1, $payload['historical_patterns']);
        $this->assertSame('bensin', $payload['historical_patterns'][0]['keyword']);
    }

    public function test_both_templates_expand_all_variables(): void
    {
        $singleResult = $this->renderer->renderSingle($this->makeContext());
        $multiResult = $this->renderer->renderMulti($this->makeContext());

        $singlePayload = json_decode($singleResult, true);
        $multiPayload = json_decode($multiResult, true);

        // No unexpanded variables should remain
        $this->assertStringNotContainsString('{{SCOPE_RULE}}', $singlePayload['instruction']);
        $this->assertStringNotContainsString('{{WALLET_NULL_RULE}}', $singlePayload['instruction']);
        $this->assertStringNotContainsString('{{AMOUNT_RULE}}', $singlePayload['instruction']);
        $this->assertStringNotContainsString('{{AMOUNT_SHORTHAND}}', $singlePayload['instruction']);

        $this->assertStringNotContainsString('{{', $multiPayload['instruction']);
        $this->assertStringNotContainsString('{{', $singlePayload['instruction']);
    }

    private function makeContext(array $overrides = []): AIContext
    {
        return new AIContext(
            userInput: $overrides['userInput'] ?? 'test transaction',
            conversationId: null,
            wallets: $overrides['wallets'] ?? [
                ['id' => 1, 'name' => 'Cash'],
                ['id' => 2, 'name' => 'BCA'],
            ],
            categories: $overrides['categories'] ?? [
                ['id' => 1, 'name' => 'Makanan'],
                ['id' => 2, 'name' => 'Transport'],
            ],
            keywordAliases: $overrides['aliases'] ?? [],
            activeMemories: $overrides['memories'] ?? [],
            today: '2026-07-27',
            timezone: 'Asia/Jakarta',
            locale: 'id',
        );
    }
}
