<?php

namespace Tests\Feature\Chat;

use App\Chat\ChatApplicationService;
use App\Chat\Formatters\WebFormatter;
use App\Jobs\ProcessChatMessageJob;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebChatAsyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    public function test_send_message_returns_queued_and_persists_pending_messages(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->postJson('/chat/message', ['message' => 'halo'])
            ->assertStatus(202)
            ->assertJsonPath('queued', true);

        $conversationId = $response->json('conversation_id');
        $this->assertNotNull($conversationId);

        Queue::assertPushed(ProcessChatMessageJob::class, fn ($job) => $job->userId === $this->user->id);

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversationId,
            'role' => 'user',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversationId,
            'role' => 'assistant',
            'status' => 'pending',
        ]);
    }

    public function test_message_status_endpoint_returns_pending_then_completed(): void
    {
        $conversation = Conversation::create(['user_id' => $this->user->id, 'is_active' => true]);
        $botMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => [],
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)
            ->getJson("/chat/message/{$botMessage->id}/status")
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('bot_message.id', $botMessage->id);

        $botMessage->update([
            'status' => 'completed',
            'content' => [['type' => 'text', 'text' => 'ok']],
        ]);

        $this->actingAs($this->user)
            ->getJson("/chat/message/{$botMessage->id}/status")
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('bot_message.content.0.text', 'ok');
    }

    public function test_message_status_requires_ownership(): void
    {
        $other = User::factory()->create();
        $conversation = Conversation::create(['user_id' => $other->id, 'is_active' => true]);
        $botMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => [],
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)
            ->getJson("/chat/message/{$botMessage->id}/status")
            ->assertNotFound();
    }

    public function test_process_chat_message_job_completes_pending_message(): void
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

        $botMessage->refresh();
        $this->assertSame('completed', $botMessage->status);
        $this->assertNotEmpty($botMessage->content);
        $this->assertNull($botMessage->error_message);
    }

    public function test_process_chat_message_job_marks_failed_on_exception(): void
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

        $this->mock(ChatApplicationService::class, function ($mock) {
            $mock->shouldReceive('handleMessage')->once()->andThrow(new \RuntimeException('LLM down'));
        });

        $job = new ProcessChatMessageJob($this->user->id, $conversation->id, $userMessage->id, $botMessage->id);
        $job->handle(app(ChatApplicationService::class), app(WebFormatter::class));

        $botMessage->refresh();
        $this->assertSame('failed', $botMessage->status);
        $this->assertSame('LLM down', $botMessage->error_message);
        $this->assertNotEmpty($botMessage->content);
    }
}
