<?php

namespace Tests\Feature\Push;

use App\Chat\ChatApplicationService;
use App\Chat\Formatters\WebFormatter;
use App\Jobs\ProcessChatMessageJob;
use App\Jobs\SendPushNotificationJob;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChatPushTriggerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
        $this->user->pushSubscriptions()->create([
            'endpoint' => 'https://example.com/ep',
            'p256dh' => 'x',
            'auth' => 'y',
        ]);
    }

    private function runJob(): void
    {
        $conversation = Conversation::create(['user_id' => $this->user->id, 'is_active' => true]);
        $userMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => [['type' => 'text', 'text' => '/saldo']],
            'raw_text' => '/saldo',
            'status' => 'completed',
        ]);
        $botMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => [],
            'status' => 'pending',
        ]);

        $job = new ProcessChatMessageJob($this->user->id, $conversation->id, $userMessage->id, $botMessage->id);
        $job->handle(app(ChatApplicationService::class), app(WebFormatter::class));
    }

    public function test_reply_success_dispatches_push(): void
    {
        Queue::fake();

        $this->runJob();

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) {
            return $job->userId === $this->user->id
                && $job->payload['url'] === '/chat';
        });
    }

    public function test_reply_success_skips_push_when_no_subscription(): void
    {
        Queue::fake();
        $this->user->pushSubscriptions()->delete();

        $this->runJob();

        Queue::assertNotPushed(SendPushNotificationJob::class);
    }

    public function test_reply_failure_dispatches_push(): void
    {
        Queue::fake();

        $this->mock(ChatApplicationService::class, function ($mock) {
            $mock->shouldReceive('handleMessage')->once()->andThrow(new \RuntimeException('LLM down'));
        });

        $this->runJob();

        Queue::assertPushed(SendPushNotificationJob::class, fn ($job) => $job->payload['url'] === '/chat');
    }
}
