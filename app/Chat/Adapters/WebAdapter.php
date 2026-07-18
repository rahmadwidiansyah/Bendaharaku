<?php

declare(strict_types=1);

namespace App\Chat\Adapters;

use App\Chat\ChatApplicationService;
use App\Chat\DTOs\ChatContext;
use App\Chat\DTOs\ChatRequest;
use App\Chat\DTOs\ChatResponse;
use App\Chat\Formatters\WebFormatter;
use App\Enums\ChatPlatform;
use App\Models\User;
use App\Models\Conversation;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;

/**
 * WebAdapter — Adapter untuk platform Web Chat.
 *
 * Tanggung jawab:
 * 1. Menerima HTTP request data (teks, user, conversation_id)
 * 2. Resolve atau buat Conversation aktif untuk user
 * 3. Simpan pesan user ke chat_messages
 * 4. Delegate ke ChatApplicationService
 * 5. Format ChatResponse via WebFormatter → JSON array
 * 6. Simpan respons bot ke chat_messages
 * 7. Return data terstruktur untuk HTTP response
 *
 * Tidak ada AI logic, tidak ada business rule di sini.
 * Tidak ada Telegram-specific code.
 */
class WebAdapter
{
    public function __construct(
        private readonly ChatApplicationService $chatService,
        private readonly WebFormatter           $formatter,
    ) {}

    /**
     * Proses satu pesan dari Web Chat.
     *
     * @param  User    $user          User yang mengirim pesan
     * @param  string  $rawMessage    Teks mentah dari user
     * @param  int|null $conversationId  ID conversation (null = gunakan/buat active)
     * @return array   JSON-ready response
     */
    public function handle(User $user, string $rawMessage, ?int $conversationId = null): array
    {
        $startTime = microtime(true);

        // 1. Resolve conversation
        $conversation = $this->resolveConversation($user, $conversationId);

        // 2. Resolve locale & timezone
        $locale   = ChatContext::resolveLocale($user->locale, null);
        $timezone = $user->timezone ?? 'Asia/Jakarta';

        // 3. Simpan pesan user
        $userMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => [['type' => 'text', 'text' => $rawMessage]],
            'raw_text'        => $rawMessage,
            'metadata'        => null,
        ]);

        // 4. Bangun ChatContext + ChatRequest
        $context = ChatContext::make(
            platform:       ChatPlatform::Web,
            conversationId: (string) $conversation->id,
            locale:         $locale,
            timezone:       $timezone,
            sessionId:      (string) $conversation->id,
            metadata:       ['web_message_id' => $userMessage->id],
        );

        $request = ChatRequest::make(
            rawMessage: $rawMessage,
            user:       $user,
            context:    $context,
        );

        // 5. Proses via ChatApplicationService
        $response = $this->chatService->handleMessage($request);

        // 6. Format respons ke JSON array untuk frontend
        $formatted = $this->formatter->format($response, $context);

        $latency = (int) round((microtime(true) - $startTime) * 1000);

        // 7. Simpan respons bot ke database
        $botMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $formatted['components'],
            'raw_text'        => null,
            'metadata'        => array_merge($response->metadata, [
                'intent'     => $response->intent->value,
                'success'    => $response->success,
                'latency_ms' => $latency,
            ]),
        ]);

        // 8. Auto-set judul conversation dari pesan pertama (jika belum ada)
        if (!$conversation->title && $conversation->messages()->count() <= 2) {
            $conversation->update([
                'title' => mb_substr($rawMessage, 0, 60),
            ]);
        }

        Log::info('WebAdapter: message processed', [
            'trace_id'        => $context->traceId,
            'user_id'         => $user->id,
            'conversation_id' => $conversation->id,
            'intent'          => $response->intent->value,
            'success'         => $response->success,
            'latency_ms'      => $latency,
        ]);

        return [
            'success'         => $response->success,
            'conversation_id' => $conversation->id,
            'user_message'    => [
                'id'         => $userMessage->id,
                'role'       => 'user',
                'content'    => [['type' => 'text', 'text' => $rawMessage]],
                'created_at' => $userMessage->created_at->toIso8601String(),
            ],
            'bot_message'     => [
                'id'         => $botMessage->id,
                'role'       => 'assistant',
                'content'    => $formatted['components'],
                'metadata'   => [
                    'intent'    => $response->intent->value,
                    'success'   => $response->success,
                    'trace_id'  => $context->traceId,
                    'latency_ms' => $latency,
                ],
                'created_at' => $botMessage->created_at->toIso8601String(),
            ],
        ];
    }

    /**
     * Ambil riwayat pesan dari conversation.
     *
     * @param  Conversation  $conversation
     * @param  int           $limit    Jumlah pesan terbaru
     * @param  int|null      $before   Cursor ID (untuk pagination ke belakang)
     * @return array
     */
    public function getHistory(Conversation $conversation, int $limit = 30, ?int $before = null): array
    {
        $query = $conversation->messages()
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($before) {
            $query->where('id', '<', $before);
        }

        $messages = $query->get()->reverse()->values();

        return $messages->map(function (ChatMessage $msg) {
            return [
                'id'         => $msg->id,
                'role'       => $msg->role,
                'content'    => $msg->content,
                'metadata'   => $msg->metadata,
                'created_at' => $msg->created_at->toIso8601String(),
            ];
        })->all();
    }

    // ── Private ───────────────────────────────────────────────────

    /**
     * Resolve conversation: gunakan yang ada atau buat baru.
     *
     * Phase awal: satu active conversation per user.
     * Phase future: user bisa membuat conversation baru, memilih conversation.
     */
    private function resolveConversation(User $user, ?int $conversationId): Conversation
    {
        // Jika ID spesifik diberikan, gunakan itu (validasi ownership)
        if ($conversationId) {
            $conversation = Conversation::where('id', $conversationId)
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        // Cari conversation aktif yang sudah ada
        $existing = Conversation::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        // Buat conversation baru
        return Conversation::create([
            'user_id'   => $user->id,
            'title'     => null,
            'is_active' => true,
        ]);
    }
}
