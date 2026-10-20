<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Chat\Formatters\WebFormatter;
use App\Jobs\EvidenceLlmGroupingJob;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Evidence;
use App\Models\User;
use App\Services\Evidence\LlmEvidenceGroupingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceLlmGroupingRetryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['push_notifications' => false]);
        $this->conversation = Conversation::create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    public function test_ocr_wait_releases_job_without_blocking_and_sets_waiting_message(): void
    {
        $this->mock(LlmEvidenceGroupingService::class, function ($mock) {
            $mock->shouldNotReceive('group');
        });

        $evidence = Evidence::factory()->for($this->user)->create(['ocr_text' => null]);
        $botMessage = $this->makeBotMessage($evidence);

        $job = new EvidenceLlmGroupingJob($evidence->id, $this->user->id, $botMessage->id);
        $job->handle(app(LlmEvidenceGroupingService::class), app(WebFormatter::class));

        $botMessage->refresh();

        $this->assertSame('processing', $botMessage->status);
        $this->assertNotEmpty($botMessage->metadata['ocr_wait_started_at'] ?? null);
        $this->assertNotEmpty($botMessage->metadata['waiting_message'] ?? null);
    }

    public function test_retryable_ai_failure_requeues_once_then_fails(): void
    {
        $this->mock(LlmEvidenceGroupingService::class, function ($mock) {
            $mock->shouldReceive('group')->twice()->andReturn([
                'success' => false,
                'error_code' => 'AI_POOL_EXHAUSTED',
                'retryable' => true,
                'message' => 'Semua AI sedang sibuk.',
            ]);
        });

        $evidence = Evidence::factory()->for($this->user)->create([
            'ocr_text' => 'Total 15000',
        ]);
        $botMessage = $this->makeBotMessage($evidence);

        $job = new EvidenceLlmGroupingJob($evidence->id, $this->user->id, $botMessage->id);
        $job->handle(app(LlmEvidenceGroupingService::class), app(WebFormatter::class));

        $botMessage->refresh();

        $this->assertSame('processing', $botMessage->status);
        $this->assertSame(1, $botMessage->metadata['ai_retry_count'] ?? null);
        $this->assertSame('AI_POOL_EXHAUSTED', $botMessage->metadata['last_error_code'] ?? null);
        $this->assertNotEmpty($botMessage->metadata['waiting_message'] ?? null);
        $this->assertSame([], $botMessage->content);

        $job->handle(app(LlmEvidenceGroupingService::class), app(WebFormatter::class));

        $botMessage->refresh();

        $this->assertSame('failed', $botMessage->status);
        $this->assertSame('AI_POOL_EXHAUSTED', $botMessage->metadata['error_code'] ?? null);
        $this->assertArrayHasKey('waiting_message', $botMessage->metadata);
        $this->assertNull($botMessage->metadata['waiting_message']);
        $this->assertSame('error', $botMessage->content[0]['type'] ?? null);
    }

    private function makeBotMessage(Evidence $evidence): ChatMessage
    {
        return ChatMessage::create([
            'conversation_id' => $this->conversation->id,
            'role' => 'assistant',
            'content' => [],
            'status' => 'pending',
            'metadata' => ['evidence_uuid' => $evidence->uuid],
        ]);
    }
}
