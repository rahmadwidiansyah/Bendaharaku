<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Chat\Adapters\WebAdapter;
use App\Chat\ChatCommandRegistry;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * WebChatController — HTTP layer untuk fitur Chat di Web.
 *
 * Tanggung jawab:
 * - Render halaman Chat (Inertia)
 * - Terima pesan dari frontend, delegate ke WebAdapter
 * - Serve riwayat pesan untuk initial load + pagination
 * - Serve daftar command dari registry
 *
 * Tidak ada AI logic di sini.
 * Tidak ada business rule — semua di WebAdapter & ChatApplicationService.
 */
class WebChatController extends Controller
{
    public function __construct(
        private readonly WebAdapter          $adapter,
        private readonly ChatCommandRegistry $commandRegistry,
    ) {}

    /**
     * GET /chat
     * Render halaman Chat dengan initial data.
     */
    public function index(Request $request): Response
    {
        $user   = $request->user();
        $locale = $user->locale ?? 'id';

        // Resolve atau buat conversation aktif
        $conversation = Conversation::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->latest()
            ->first();

        // Load pesan terbaru (30 pesan, ambil 31 untuk deteksi hasMore)
        $messages = [];
        $hasMore  = false;
        if ($conversation) {
            $raw     = $this->adapter->getHistory($conversation, limit: 31);
            $hasMore = count($raw) > 30;
            if ($hasMore) {
                array_shift($raw); // buang yang paling lama (ke-31), sisakan 30 terbaru
            }
            $messages = $raw;
        }

        return Inertia::render('Chat/Index', [
            'conversation'   => $conversation ? [
                'id'    => $conversation->id,
                'title' => $conversation->title,
            ] : null,
            'initialMessages' => $messages,
            'initialHasMore'  => $hasMore,
            'botProfile'      => [
                'name'   => $user->bot_name ?? 'Ken-Chan',
                'avatar' => $user->bot_avatar
                    ? asset('storage/' . $user->bot_avatar)
                    : null,
            ],
            'commands'        => $this->commandRegistry->toApiResponse('web', $locale),
        ]);
    }

    /**
     * POST /chat/message
     * Terima dan proses pesan dari user.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'         => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        try {
            $result = $this->adapter->handle(
                user:           $request->user(),
                rawMessage:     $validated['message'],
                conversationId: $validated['conversation_id'] ?? null,
            );

            return response()->json($result);

        } catch (Throwable $e) {
            Log::error('WebChatController: sendMessage error', [
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'bot_message' => [
                    'id'       => null,
                    'role'     => 'assistant',
                    'content'  => [[
                        'type'    => 'error',
                        'message' => __('chat.error.system'),
                        'severity' => 'error',
                    ]],
                    'metadata'   => [],
                    'created_at' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * GET /chat/history
     * Load riwayat pesan (pagination ke belakang).
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['nullable', 'integer'],
            'before'          => ['nullable', 'integer'],
            'limit'           => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();

        // Resolve conversation
        $conversationId = $validated['conversation_id'] ?? null;
        $conversation = $conversationId
            ? Conversation::where('id', $conversationId)->where('user_id', $user->id)->first()
            : Conversation::where('user_id', $user->id)->where('is_active', true)->latest()->first();

        if (!$conversation) {
            return response()->json(['messages' => [], 'has_more' => false]);
        }

        $limit    = $validated['limit'] ?? 30;
        $before   = $validated['before'] ?? null;
        $messages = $this->adapter->getHistory($conversation, $limit + 1, $before);

        $hasMore = count($messages) > $limit;
        if ($hasMore) {
            array_pop($messages);
        }

        return response()->json([
            'messages' => $messages,
            'has_more' => $hasMore,
        ]);
    }

    /**
     * GET /chat/commands
     * Daftar command yang tersedia (dari registry).
     * Dipakai saat frontend perlu refresh daftar command.
     */
    public function commands(Request $request): JsonResponse
    {
        $locale   = $request->user()->locale ?? 'id';
        $commands = $this->commandRegistry->toApiResponse('web', $locale);

        return response()->json(['commands' => $commands]);
    }
}
