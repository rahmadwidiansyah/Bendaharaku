<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Chat\Components\TextComponent;
use App\Chat\DTOs\ChatContext;
use App\Chat\Services\ChatResponseConverter;
use App\Chat\Services\DraftViewModelBuilder;
use App\DTO\MultiTransactionResult;
use App\Enums\ChatPlatform;
use App\Models\TransactionLog;
use PHPUnit\Framework\TestCase;

class ChatResponseConverterRoutingTest extends TestCase
{
    private ChatResponseConverter $converter;

    private ChatContext $context;

    protected function setUp(): void
    {
        $this->converter = new ChatResponseConverter(new DraftViewModelBuilder);
        $this->context = new ChatContext(ChatPlatform::Web, 'conv-1', 'id', 'Asia/Jakarta', 'trace-1');
    }

    public function test_build_metadata_summarizes_single_routing_without_sensitive_keys(): void
    {
        $metadata = $this->converter->buildMetadata([
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
            'confidence' => 0.9,
            'routing' => [
                'trace_id' => 'tr-1',
                'selected_provider' => 'gemini',
                'selected_model' => 'gemini-2.5-flash',
                'selected_account_alias' => 'A1',
                'selection_method' => 'fallback',
                'attempt_count' => 2,
                'final_status' => 'success',
                'fallback_from' => 'gemini/gemini-2.0-flash',
                'fallback_to' => 'gemini/gemini-2.5-flash',
                'fallback_reason' => 'MODEL_NOT_FOUND',
                'attempts' => [
                    ['provider' => 'gemini', 'model' => 'gemini-2.0-flash', 'category' => 'MODEL_NOT_FOUND', 'latency_ms' => 120, 'message' => 'jangan bocor', 'account_id' => 7],
                    ['provider' => 'gemini', 'model' => 'gemini-2.5-flash', 'category' => 'OK', 'latency_ms' => 200],
                ],
                'failures' => [['message' => 'jangan bocor']],
                'skipped' => ['jangan bocor'],
            ],
        ], $this->context, 500);

        $routing = $metadata['routing'];
        $this->assertSame('gemini/gemini-2.0-flash', $routing['fallback_from']);
        $this->assertSame('gemini/gemini-2.5-flash', $routing['fallback_to']);
        $this->assertSame('MODEL_NOT_FOUND', $routing['fallback_reason']);
        $this->assertSame('A1', $routing['selected_account_alias']);
        $this->assertCount(2, $routing['attempts']);
        $this->assertArrayNotHasKey('failures', $routing);
        $this->assertArrayNotHasKey('skipped', $routing);
        $this->assertArrayNotHasKey('account_id', $routing['attempts'][0]);
        $this->assertArrayNotHasKey('message', $routing['attempts'][0]);
    }

    public function test_build_metadata_caps_attempts_at_eight(): void
    {
        $attempts = array_map(
            fn (int $i) => ['provider' => 'gemini', 'model' => "m{$i}", 'category' => 'OK', 'latency_ms' => $i],
            range(1, 12),
        );

        $metadata = $this->converter->buildMetadata([
            'provider' => 'gemini',
            'routing' => [
                'selected_provider' => 'gemini',
                'selected_model' => 'm12',
                'attempts' => $attempts,
            ],
        ], $this->context, 100);

        $this->assertCount(8, $metadata['routing']['attempts']);
        $this->assertSame('m8', $metadata['routing']['attempts'][7]['model']);
    }

    public function test_build_metadata_reads_routing_from_multi_result_dto(): void
    {
        $dto = new MultiTransactionResult(
            results: [],
            provider: 'gemini',
            model: 'gemini-2.5-flash',
            confidence: 0.9,
            routing: [
                'selected_provider' => 'gemini',
                'selected_model' => 'gemini-2.5-flash',
                'fallback_from' => 'gemini/gemini-2.0-flash',
                'fallback_to' => 'gemini/gemini-2.5-flash',
                'final_status' => 'success',
            ],
        );

        $metadata = $this->converter->buildMetadata(['multi_result' => $dto, 'usage' => []], $this->context, 250);

        $this->assertSame('gemini', $metadata['provider']);
        $this->assertSame('gemini-2.5-flash', $metadata['model']);
        $this->assertSame('gemini/gemini-2.0-flash', $metadata['routing']['fallback_from']);
    }

    public function test_build_metadata_keeps_local_fallback_summary(): void
    {
        $metadata = $this->converter->buildMetadata([
            'provider' => 'python-nlp',
            'model' => 'local',
            'routing' => [
                'selection_method' => 'local_python_fallback',
                'attempt_count' => 1,
                'final_status' => 'local_fallback',
                'fallback_reason' => 'Semua kandidat AI gagal.',
            ],
        ], $this->context, 80);

        $this->assertSame('local_fallback', $metadata['routing']['final_status']);
        $this->assertSame('Semua kandidat AI gagal.', $metadata['routing']['fallback_reason']);
        $this->assertArrayNotHasKey('fallback_from', $metadata['routing']);
    }

    public function test_build_metadata_omits_routing_when_absent(): void
    {
        $metadata = $this->converter->buildMetadata([
            'provider' => 'python-nlp',
            'model' => 'local',
        ], $this->context, 50);

        $this->assertArrayNotHasKey('routing', $metadata);
    }

    private function providerLabelComponents(array $components): array
    {
        return array_values(array_filter(
            $components,
            fn ($c) => $c instanceof TextComponent
                && $c->translationKey === 'chat.transaction.label_ai_provider'
        ));
    }

    private function makeTransaction(): TransactionLog
    {
        return new TransactionLog([
            'amount' => 15000,
            'subject' => 'Nasi goreng',
            'is_cleared' => true,
        ]);
    }

    public function test_label_provider_hidden_when_natural_message_present(): void
    {
        $response = $this->converter->convertSingleSuccess(
            result: ['transaction' => $this->makeTransaction()],
            context: $this->context,
            metadata: ['provider' => 'gemini', 'confidence' => 0.95],
            originalText: 'beli nasi goreng 15 ribu',
            assistantMessage: 'Siap, sudah kucatat.',
        );

        $texts = $this->providerLabelComponents($response->components);
        $this->assertSame([], $texts);
        $this->assertInstanceOf(TextComponent::class, $response->components[0]);
        $this->assertSame('Siap, sudah kucatat.', $response->components[0]->translationKey);
    }

    public function test_label_provider_kept_when_no_natural_message(): void
    {
        $response = $this->converter->convertSingleSuccess(
            result: ['transaction' => $this->makeTransaction()],
            context: $this->context,
            metadata: ['provider' => 'gemini', 'confidence' => 0.95],
            originalText: 'beli nasi goreng 15 ribu',
        );

        $texts = $this->providerLabelComponents($response->components);
        $this->assertCount(1, $texts);
    }
}
