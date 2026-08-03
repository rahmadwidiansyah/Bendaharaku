<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Chat\Adapters\WebAdapter;
use App\Chat\ChatApplicationService;
use App\Chat\DTOs\ChatContext;
use App\Chat\DTOs\ChatRequest;
use App\Chat\Formatters\WebFormatter;
use App\Enums\ChatPlatform;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Push\PushGate;
use App\Services\Push\PushPayloadBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ProcessChatMessageJob — proses pesan chat + panggilan LLM di background queue.
 *
 * Request HTTP hanya menyimpan pesan user + pesan bot pending lalu dispatch job.
 * Job ini menjalankan pipeline chat (orchestrator → LLM → formatter) dan
 * memperbarui pesan bot, sehingga proses tidak batal saat user pindah halaman.
 *
 * Tries = 1: pipeline chat dapat membuat transaksi/draft, retry berisiko duplikasi.
 */
class ProcessChatMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public int $userId,
        public int $conversationId,
        public int $userMessageId,
        public int $botMessageId,
    ) {}

    public function handle(ChatApplicationService $chatService, WebFormatter $formatter): void
    {
        $user = User::findOrFail($this->userId);
        $conversation = Conversation::findOrFail($this->conversationId);
        $userMessage = ChatMessage::findOrFail($this->userMessageId);
        $botMessage = ChatMessage::findOrFail($this->botMessageId);

        $startTime = microtime(true);
        $rawMessage = (string) ($userMessage->content[0]['text'] ?? $userMessage->raw_text ?? '');

        $locale = ChatContext::resolveLocale($user->locale, null);
        $timezone = $user->timezone ?? 'Asia/Jakarta';

        $context = ChatContext::make(
            platform: ChatPlatform::Web,
            conversationId: (string) $conversation->id,
            locale: $locale,
            timezone: $timezone,
            sessionId: (string) $conversation->id,
            metadata: ['web_message_id' => $userMessage->id],
        );

        $request = ChatRequest::make(
            rawMessage: $rawMessage,
            user: $user,
            context: $context,
        );

        $botMessage->update(['status' => 'processing', 'error_message' => null]);

        try {
            $response = $chatService->handleMessage($request);
            $formatted = $formatter->format($response, $context);
            $latency = (int) round((microtime(true) - $startTime) * 1000);

            $botMessage->update([
                'status' => 'completed',
                'content' => $formatted['components'],
                'raw_text' => WebAdapter::extractTextFromComponents($formatted['components']),
                'error_message' => null,
                'metadata' => array_merge($response->metadata, [
                    'intent' => $response->intent->value,
                    'success' => $response->success,
                    'latency_ms' => $latency,
                    'raw_prompt' => $rawMessage,
                ]),
            ]);

            // Auto-set judul conversation dari pesan pertama
            if (! $conversation->title && $conversation->messages()->count() <= 2) {
                $conversation->update([
                    'title' => mb_substr($rawMessage, 0, 60),
                ]);
            }

            PushGate::dispatch(
                $user,
                PushPayloadBuilder::chatReplyReady($user, (string) $botMessage->raw_text)
            );

            Log::info('ProcessChatMessageJob: message processed', [
                'trace_id' => $context->traceId,
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'intent' => $response->intent->value,
                'success' => $response->success,
                'latency_ms' => $latency,
            ]);
        } catch (Throwable $e) {
            // Simpan error sebagai konten bubble error agar riwayat tetap lengkap
            $errorMsg = __('chat.error.system');
            $latency = (int) round((microtime(true) - $startTime) * 1000);

            $botMessage->update([
                'status' => 'failed',
                'content' => [[
                    'type' => 'error',
                    'message' => $errorMsg,
                    'severity' => 'error',
                ]],
                'raw_text' => $errorMsg,
                'error_message' => $e->getMessage(),
                'metadata' => [
                    'trace_id' => $context->traceId,
                    'error' => true,
                    'latency_ms' => $latency,
                    'exception' => get_class($e),
                ],
            ]);

            PushGate::dispatch($user, PushPayloadBuilder::chatReplyFailed($user));

            Log::error('ProcessChatMessageJob: handle exception', [
                'trace_id' => $context->traceId,
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'latency_ms' => $latency,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
