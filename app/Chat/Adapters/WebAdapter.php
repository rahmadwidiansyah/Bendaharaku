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
use App\Models\TransactionLog;
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

        // 1. Resolve conversation — selalu ada, tidak pernah null
        $conversation = $this->resolveConversation($user, $conversationId);

        // 2. Resolve locale & timezone
        $locale   = ChatContext::resolveLocale($user->locale, null);
        $timezone = $user->timezone ?? 'Asia/Jakarta';

        // 3. Simpan pesan user ke DB SEBELUM memproses AI.
        //    Ini kritis: user message harus persisted dulu, sehingga
        //    jika AI crash sekalipun, conversation tetap ada dan
        //    conversation_id tetap valid di response.
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

        // 5–7. Proses AI + simpan bot message.
        //      Dibungkus try/catch agar jika AI atau formatter crash,
        //      kita tetap bisa return conversation_id + user_message_id
        //      yang valid ke frontend. Dengan ini:
        //      - conversationId frontend tidak jadi null
        //      - history tidak hilang saat user kembali ke halaman chat
        try {
            $response  = $this->chatService->handleMessage($request);
            $formatted = $this->formatter->format($response, $context);
            $latency   = (int) round((microtime(true) - $startTime) * 1000);

            $botMessage = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'role'            => 'assistant',
                'content'         => $formatted['components'],
                'raw_text'        => null,
                'metadata'        => array_merge($response->metadata, [
                    'intent'     => $response->intent->value,
                    'success'    => $response->success,
                    'latency_ms' => $latency,
                    'raw_prompt' => $rawMessage,
                ]),
            ]);

            // Auto-set judul conversation dari pesan pertama
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
                    'metadata'   => [],
                    'created_at' => $userMessage->created_at->toIso8601String(),
                ],
                'bot_message' => [
                    'id'         => $botMessage->id,
                    'role'       => 'assistant',
                    'content'    => $formatted['components'],
                    'metadata'   => [
                        'intent'       => $response->intent->value,
                        'success'      => $response->success,
                        'trace_id'     => $context->traceId,
                        'latency_ms'   => $latency,
                        'provider'     => $response->meta('provider'),
                        'model'        => $response->meta('model'),
                        'confidence'   => $response->meta('confidence'),
                        'total_tokens' => $response->meta('total_tokens'),
                        'raw_prompt'   => $rawMessage,
                    ],
                    'created_at' => $botMessage->created_at->toIso8601String(),
                ],
            ];

        } catch (\Throwable $e) {
            // AI / formatter crash — simpan error message ke DB agar
            // riwayat percakapan tetap lengkap dan bisa diaudit
            $latency    = (int) round((microtime(true) - $startTime) * 1000);
            $errorMsg   = __('chat.error.system');

            $botMessage = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'role'            => 'assistant',
                'content'         => [[
                    'type'     => 'error',
                    'message'  => $errorMsg,
                    'severity' => 'error',
                ]],
                'raw_text'        => null,
                'metadata'        => [
                    'trace_id'   => $context->traceId,
                    'error'      => true,
                    'latency_ms' => $latency,
                    'exception'  => get_class($e),
                ],
            ]);

            Log::error('WebAdapter: handle exception', [
                'trace_id'        => $context->traceId,
                'user_id'         => $user->id,
                'conversation_id' => $conversation->id,
                'latency_ms'      => $latency,
                'exception'       => $e->getMessage(),
            ]);

            // Tetap return conversation_id + user_message + bot_message
            // agar frontend tidak kehilangan conversationId
            return [
                'success'         => false,
                'conversation_id' => $conversation->id,
                'user_message'    => [
                    'id'         => $userMessage->id,
                    'role'       => 'user',
                    'content'    => [['type' => 'text', 'text' => $rawMessage]],
                    'metadata'   => [],
                    'created_at' => $userMessage->created_at->toIso8601String(),
                ],
                'bot_message' => [
                    'id'         => $botMessage->id,
                    'role'       => 'assistant',
                    'content'    => [[
                        'type'     => 'error',
                        'message'  => $errorMsg,
                        'severity' => 'error',
                    ]],
                    'metadata'   => ['error' => true, 'latency_ms' => $latency],
                    'created_at' => $botMessage->created_at->toIso8601String(),
                ],
            ];
        }
    }

    /**
     * Ambil semua pesan sejak tanggal tertentu (untuk initial load 7 hari).
     * Diurutkan ascending (chronological) untuk render chat.
     */
    public function getHistorySince(Conversation $conversation, \Carbon\Carbon $since): array
    {
        $messages = $conversation->messages()
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->get();

        return $this->syncTransactionCardsWithDb($messages, $conversation->user_id)->map(fn (ChatMessage $msg) => [
            'id'         => $msg->id,
            'role'       => $msg->role,
            'content'    => $msg->content ?? [],
            'metadata'   => $msg->metadata ?? [],
            'created_at' => $msg->created_at->toIso8601String(),
        ])->all();
    }

    /**
     * Ambil riwayat pesan dari conversation (untuk pagination ke belakang).
     *
     * @param  Conversation  $conversation
     * @param  int           $limit    Jumlah pesan terbaru
     * @param  int|null      $before   Cursor ID (untuk pagination ke belakang)
     * @return array
     */
    public function getHistory(Conversation $conversation, int $limit = 30, ?int $before = null): array
    {
        $query = $conversation->messages()
            ->orderByDesc('id')
            ->limit($limit);

        if ($before) {
            $query->where('id', '<', $before);
        }

        // Ambil N terbaru (desc), lalu sortBy id ascending → chronological
        $messages = $query->get()->sortBy('id')->values();

        return $this->syncTransactionCardsWithDb($messages, $conversation->user_id)->map(function (ChatMessage $msg) {
            return [
                'id'         => $msg->id,
                'role'       => $msg->role,
                'content'    => $msg->content ?? [],
                'metadata'   => $msg->metadata ?? [],
                'created_at' => $msg->created_at->toIso8601String(),
            ];
        })->all();
    }

    // ── Private ───────────────────────────────────────────────────

    private function syncTransactionCardsWithDb($messages, int $userId)
    {
        $transactionIds = $messages
            ->flatMap(function (ChatMessage $message) {
                return collect($message->content ?? [])
                    ->filter(fn ($component) => ($component['type'] ?? null) === 'transaction_card')
                    ->map(fn ($component) => (int) ($component['transaction']['id'] ?? 0))
                    ->filter();
            })
            ->unique()
            ->values();

        if ($transactionIds->isEmpty()) {
            return $messages;
        }

        $existingIds = TransactionLog::query()
            ->where('user_id', $userId)
            ->whereIn('id', $transactionIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $existingMap = array_flip($existingIds);

        return $messages->map(function (ChatMessage $message) use ($existingMap) {
            $content = $message->content ?? [];
            $changed = false;

            foreach ($content as &$component) {
                if (($component['type'] ?? null) !== 'transaction_card') {
                    continue;
                }

                $transactionId = (int) ($component['transaction']['id'] ?? 0);
                if ($transactionId && !isset($existingMap[$transactionId])) {
                    $component['needs_wallet'] = false;
                    $component['transaction']['is_cancelled'] = true;
                    $component['transaction']['is_cleared'] = false;
                    $changed = true;
                }
            }

            if ($changed) {
                $message->forceFill(['content' => $content])->save();
            }

            return $message;
        });
    }

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
