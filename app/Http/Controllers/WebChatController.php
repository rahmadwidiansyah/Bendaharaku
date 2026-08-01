<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessTransactionAction;
use App\Chat\Adapters\WebAdapter;
use App\Chat\ChatCommandRegistry;
use App\Enums\TransactionIntent;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Services\Chat\DraftConfirmationService;
use App\Support\MoneyFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
 * - Konfirmasi/batalkan/assign wallet untuk TransactionDraft dan TransactionLog
 *
 * Tidak ada AI logic di sini.
 * Tidak ada business rule — semua di WebAdapter, ChatApplicationService,
 * DraftConfirmationService, dan ProcessTransactionAction.
 */
class WebChatController extends Controller
{
    public function __construct(
        private readonly WebAdapter $adapter,
        private readonly ChatCommandRegistry $commandRegistry,
        private readonly DraftConfirmationService $draftService,
    ) {}

    /**
     * GET /chat
     * Render halaman Chat dengan initial data.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $locale = $user->locale ?? 'id';

        // Resolve atau buat conversation aktif
        $conversation = Conversation::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->latest()
            ->first();

        // Load pesan 7 hari terakhir (ambil +1 untuk deteksi hasMore)
        $messages = [];
        $hasMore = false;
        if ($conversation) {
            $sevenDaysAgo = now()->subDays(7)->startOfDay();

            // Reset pesan yang stuck (pending/processing > 5 menit) agar frontend tidak polling selamanya
            $conversation->messages()
                ->whereIn('status', ['pending', 'processing'])
                ->where('updated_at', '<', now()->subMinutes(5))
                ->update([
                    'status' => 'failed',
                    'error_message' => 'Proses timeout. Silakan kirim ulang pesan.',
                ]);

            $raw = $this->adapter->getHistorySince($conversation, $sevenDaysAgo);

            // hasMore = true jika ada pesan lebih lama dari 7 hari yang lalu
            $hasMore = $conversation->messages()
                ->where('created_at', '<', $sevenDaysAgo)
                ->exists();

            $messages = $raw;
        }

        return Inertia::render('Chat/Index', [
            'conversation' => $conversation ? [
                'id' => $conversation->id,
                'title' => $conversation->title,
            ] : null,
            'initialMessages' => $messages,
            'initialHasMore' => $hasMore,
            'botProfile' => [
                'name' => $user->bot_name ?? 'Ken-Chan',
                'avatar' => $user->bot_avatar
                    ? asset('storage/'.$user->bot_avatar)
                    : null,
            ],
            'commands' => $this->commandRegistry->toApiResponse('web', $locale),
        ]);
    }

    /**
     * POST /chat/message
     * Terima pesan dari user dan dispatch proses AI ke background queue.
     * Response dikembalikan segera — frontend polling status pesan bot.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        try {
            $result = $this->adapter->enqueueMessage(
                user: $request->user(),
                rawMessage: $validated['message'],
                conversationId: $validated['conversation_id'] ?? null,
            );

            return response()->json($result, 202);

        } catch (Throwable $e) {
            // Fallback terakhir — hanya terjadi jika DB down atau exception
            // sebelum conversation bisa di-resolve (sangat jarang).
            // Tetap kirim conversation_id agar frontend tidak reset ke null.
            Log::error('WebChatController: sendMessage fatal error', [
                'user_id' => $request->user()->id,
                'conversation_id' => $validated['conversation_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            // Coba ambil conversation_id yang valid dari DB sebagai fallback
            $fallbackConvId = $validated['conversation_id'] ?? null;
            if (! $fallbackConvId) {
                $fallbackConv = Conversation::where('user_id', $request->user()->id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->latest()
                    ->value('id');
                $fallbackConvId = $fallbackConv;
            }

            return response()->json([
                'success' => false,
                'queued' => false,
                'conversation_id' => $fallbackConvId,
                'bot_message' => [
                    'id' => null,
                    'role' => 'assistant',
                    'status' => 'failed',
                    'content' => [[
                        'type' => 'error',
                        'message' => __('chat.error.system'),
                        'severity' => 'error',
                    ]],
                    'metadata' => ['error' => true],
                    'created_at' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * GET /chat/message/{id}/status
     * Status proses pesan bot (polling oleh frontend).
     */
    public function messageStatus(Request $request, int $id): JsonResponse
    {
        $message = ChatMessage::where('id', $id)
            ->where('role', 'assistant')
            ->whereHas('conversation', fn ($q) => $q->where('user_id', $request->user()->id))
            ->first();

        if (! $message) {
            return response()->json([
                'status' => 'not_found',
                'bot_message' => null,
            ], 404);
        }

        // Jika pesan masih pending/processing tapi sudah > 5 menit tidak diupdate,
        // anggap job stuck — tandai failed agar frontend tidak polling terus
        $isStuck = in_array($message->status, ['pending', 'processing'])
            && $message->updated_at->diffInMinutes(now()) > 5;

        if ($isStuck) {
            $message->update([
                'status' => 'failed',
                'error_message' => 'Proses timeout. Silakan kirim ulang pesan.',
            ]);
        }

        return response()->json([
            'status' => $message->status ?? 'completed',
            'error_message' => $message->error_message,
            'bot_message' => $this->adapter->formatMessageForClient($message),
        ]);
    }

    /**
     * GET /chat/history
     * Load riwayat pesan (pagination ke belakang).
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['nullable', 'integer'],
            'before' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();

        // Resolve conversation
        $conversationId = $validated['conversation_id'] ?? null;
        $conversation = $conversationId
            ? Conversation::where('id', $conversationId)->where('user_id', $user->id)->first()
            : Conversation::where('user_id', $user->id)->where('is_active', true)->latest()->first();

        if (! $conversation) {
            return response()->json(['messages' => [], 'has_more' => false]);
        }

        $limit = $validated['limit'] ?? 30;
        $before = $validated['before'] ?? null;
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
        $locale = $request->user()->locale ?? 'id';
        $commands = $this->commandRegistry->toApiResponse('web', $locale);

        return response()->json(['commands' => $commands]);
    }

    /**
     * GET /chat/wallets
     * Return daftar wallet user untuk quick selection di chat.
     */
    public function wallets(Request $request): JsonResponse
    {
        $user = $request->user();

        $wallets = $request->user()
            ->wallets()
            ->where('group_type', '!=', 'System')
            ->orderByDesc('is_pinned')
            ->orderBy('name')
            ->get(['id', 'name', 'balance', 'is_pinned']);

        $walletIds = $wallets->pluck('id')->all();
        $usageCounts = array_fill_keys($walletIds, 0);

        if (! empty($walletIds)) {
            $user->transactionLogs()
                ->where(function ($query) use ($walletIds) {
                    $query->whereIn('source_wallet_id', $walletIds)
                        ->orWhereIn('destination_wallet_id', $walletIds);
                })
                ->get(['source_wallet_id', 'destination_wallet_id'])
                ->each(function ($transaction) use (&$usageCounts) {
                    if (isset($usageCounts[$transaction->source_wallet_id])) {
                        $usageCounts[$transaction->source_wallet_id]++;
                    }
                    if (isset($usageCounts[$transaction->destination_wallet_id])) {
                        $usageCounts[$transaction->destination_wallet_id]++;
                    }
                });
        }

        $wallets = $wallets
            ->sort(function ($a, $b) use ($usageCounts) {
                $byUsage = ($usageCounts[$b->id] ?? 0) <=> ($usageCounts[$a->id] ?? 0);
                if ($byUsage !== 0) {
                    return $byUsage;
                }

                $byPinned = ((bool) $b->is_pinned) <=> ((bool) $a->is_pinned);
                if ($byPinned !== 0) {
                    return $byPinned;
                }

                return strcasecmp($a->name, $b->name);
            })
            ->take(4)
            ->values();

        return response()->json([
            'wallets' => $wallets->map(fn ($w) => [
                'id' => $w->id,
                'name' => $w->name,
            ])->values(),
        ]);
    }

    /**
     * PATCH /chat/transaction/{id}/wallet
     * Assign wallet ke draft transaksi dan konfirmasi.
     *
     * Mencoba draft di transaction_drafts terlebih dahulu.
     * Jika tidak ada (backward compat), gunakan logic lama dari transaction_logs.
     */
    public function assignWallet(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        // ── Coba cari di transaction_drafts terlebih dahulu ───────
        $draft = TransactionDraft::where('user_id', $user->id)
            ->where('status', 'pending')
            ->find($id);

        if ($draft !== null) {
            try {
                $transactionLog = $this->draftService->assignWallet(
                    draft: $draft,
                    user: $user,
                    walletId: $validated['wallet_id'],
                );

                return response()->json([
                    'success' => true,
                    'transaction' => $this->formatTransactionForChat($transactionLog),
                ]);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        // ── Backward compat: cari di transaction_logs (draft lama) ──
        $transaction = $user->transactionLogs()
            ->with(['sourceWallet', 'destinationWallet', 'category', 'type'])
            ->where('is_cleared', false)
            ->find($id);

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Draft tidak ditemukan.',
            ], 404);
        }

        // Pastikan wallet milik user
        $wallet = $user->wallets()
            ->where('group_type', '!=', 'System')
            ->findOrFail($validated['wallet_id']);

        // Resolve sisi wallet: apakah user mengisi source atau dest?
        // Income → user selalu mengisi destination (uang MASUK ke wallet user)
        // Non-Income → gunakan heuristic: jika source adalah System wallet bermakna,
        // user mengisi dest; selain itu user mengisi source.
        $typeName = strtolower($transaction->type?->name ?? '');

        if ($typeName === 'income') {
            DB::transaction(function () use ($transaction, $wallet) {
                $transaction->destination_wallet_id = $wallet->id;
                $balanceBefore = $wallet->balance;
                $wallet->increment('balance', $transaction->amount);
                $transaction->balance_before = $balanceBefore;
                $transaction->balance_after = $wallet->fresh()->balance;
                $transaction->is_cleared = true;
                $transaction->save();
            });
        } else {
            $sourceWallet = $transaction->sourceWallet;
            $destWallet = $transaction->destinationWallet;
            $sourceIsRealSystem = $sourceWallet && $sourceWallet->group_type === 'System'
                && ! str_contains(strtolower($sourceWallet->name ?? ''), 'external')
                && ! str_contains(strtolower($sourceWallet->name ?? ''), 'merchant');

            DB::transaction(function () use ($transaction, $wallet, $user, $sourceIsRealSystem) {
                if ($sourceIsRealSystem) {
                    // Source sudah ditetapkan (System Hutang / System Piutang), user mengisi dest
                    // Artinya: uang MASUK ke wallet user (tambah saldo)
                    $transaction->destination_wallet_id = $wallet->id;
                    $balanceBefore = $wallet->balance;
                    $wallet->increment('balance', $transaction->amount);
                    $transaction->balance_before = $balanceBefore;
                    $transaction->balance_after = $wallet->fresh()->balance;
                } else {
                    // Dest sudah ditetapkan (Merchant / System Piutang / System Hutang), user mengisi source
                    // Artinya: uang KELUAR dari wallet user (kurangi saldo)
                    $transaction->source_wallet_id = $wallet->id;
                    $balanceBefore = $wallet->balance;
                    if (! $user->allow_negative_balance && $wallet->balance < $transaction->amount) {
                        throw new \InvalidArgumentException('Saldo tidak mencukupi.');
                    }
                    $wallet->decrement('balance', $transaction->amount);
                    $transaction->balance_before = $balanceBefore;
                    $transaction->balance_after = $wallet->fresh()->balance;
                }
                $transaction->is_cleared = true;
                $transaction->save();
            });
        }

        $transaction->refresh()->load(['sourceWallet', 'destinationWallet', 'category', 'type']);
        $this->markTransactionUpdatedInChatHistory($user->id, $transaction);

        return response()->json([
            'success' => true,
            'transaction' => $this->formatTransactionForChat($transaction),
        ]);
    }

    /**
     * GET /chat/transaction/{id}/status
     * Cek apakah transaksi dari chat masih ada di DB.
     */
    public function transactionStatus(Request $request, int $id): JsonResponse
    {
        $transaction = $request->user()
            ->transactionLogs()
            ->with(['sourceWallet', 'destinationWallet', 'category', 'type'])
            ->find($id);

        if (! $transaction) {
            return response()->json([
                'exists' => false,
                'transaction' => [
                    'id' => $id,
                    'is_cancelled' => true,
                    'is_cleared' => false,
                ],
            ]);
        }

        return response()->json([
            'exists' => true,
            'transaction' => $this->formatTransactionForChat($transaction),
        ]);
    }

    /**
     * GET /chat/draft/{id}/status
     * Cek status TransactionDraft dari chat.
     * Digunakan frontend untuk polling status draft.
     */
    public function draftStatus(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $draft = TransactionDraft::where('user_id', $user->id)->find($id);

        if (! $draft) {
            return response()->json([
                'exists' => false,
                'is_draft' => true,
                'draft' => [
                    'id' => $id,
                    'is_cancelled' => true,
                    'is_cleared' => false,
                ],
            ]);
        }

        return response()->json([
            'exists' => true,
            'is_draft' => true,
            'draft' => $this->draftService->formatDraftForChat($draft),
        ]);
    }

    /**
     * PATCH /chat/transaction/{id}/confirm
     * Konfirmasi draft yang wallet-nya sudah lengkap.
     *
     * Mencoba draft di transaction_drafts terlebih dahulu.
     * Jika tidak ada, gunakan logic lama dari transaction_logs (backward compat).
     */
    public function confirmTransaction(Request $request, int $id, ProcessTransactionAction $action): JsonResponse
    {
        $user = $request->user();

        // ── Coba cari di transaction_drafts terlebih dahulu ───────
        $draft = TransactionDraft::where('user_id', $user->id)
            ->where('status', 'pending')
            ->find($id);

        if ($draft !== null) {
            try {
                $transactionLog = $this->draftService->confirm($draft, $user);

                return response()->json([
                    'success' => true,
                    'transaction' => $this->formatTransactionForChat($transactionLog),
                ]);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        // ── Backward compat: cari di transaction_logs ─────────────
        $transaction = $user->transactionLogs()
            ->with(['sourceWallet', 'destinationWallet', 'category', 'type'])
            ->find($id);

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi sudah batal.',
                'transaction' => [
                    'id' => $id,
                    'is_cancelled' => true,
                    'is_cleared' => false,
                ],
            ], 404);
        }

        if (! $transaction->is_cleared) {
            try {
                $action->confirm($transaction);
                $transaction->refresh()->load(['sourceWallet', 'destinationWallet', 'category', 'type']);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        // Sinkronisasi riwayat chat dengan status terbaru setelah konfirmasi
        $this->markTransactionUpdatedInChatHistory($request->user()->id, $transaction);

        return response()->json([
            'success' => true,
            'transaction' => $this->formatTransactionForChat($transaction),
        ]);
    }

    /**
     * DELETE /chat/transaction/{id}/cancel
     * Batal/hapus transaksi dari chat.
     *
     * Mencoba draft di transaction_drafts terlebih dahulu.
     * Jika tidak ada, hapus dari transaction_logs (backward compat).
     */
    public function cancelTransaction(Request $request, int $id, ProcessTransactionAction $action): JsonResponse
    {
        $user = $request->user();

        // ── Coba cari di transaction_drafts terlebih dahulu ───────
        $draft = TransactionDraft::where('user_id', $user->id)->find($id);

        if ($draft !== null) {
            try {
                $this->draftService->cancel($draft, $user);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'is_draft' => true,
                'draft' => [
                    'id' => $id,
                    'is_cancelled' => true,
                    'is_cleared' => false,
                ],
            ]);
        }

        // ── Backward compat: cari di transaction_logs ─────────────
        $transaction = $user->transactionLogs()
            ->with(['sourceWallet', 'destinationWallet', 'category', 'type'])
            ->find($id);

        if ($transaction) {
            try {
                $action->delete($transaction);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        $this->markTransactionCancelledInChatHistory($user->id, $id);

        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $id,
                'is_cancelled' => true,
                'is_cleared' => false,
            ],
        ]);
    }

    private function formatTransactionForChat(TransactionLog $transaction): array
    {
        $typeKey = TransactionIntent::typeKeyFromName($transaction->type?->name);

        return [
            'id' => $transaction->id,
            'is_draft' => false,
            'reference_number' => $transaction->reference_number,
            'amount' => $transaction->amount,
            'amount_formatted' => MoneyFormatter::rupiah($transaction->amount),
            'is_cleared' => (bool) $transaction->is_cleared,
            'is_cancelled' => false,
            'needs_wallet' => ! $transaction->is_cleared && (
                $transaction->sourceWallet?->group_type === 'System'
                || $transaction->destinationWallet?->group_type === 'System'
            ),
            'type_key' => $typeKey,
            'category' => $transaction->category?->category_name,
            'source_wallet' => $transaction->sourceWallet?->name,
            'dest_wallet' => $transaction->destinationWallet?->name,
            'subject' => $transaction->subject,
            'notes' => $transaction->notes,
            'date' => $transaction->date?->toDateString(),
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }

    private function markTransactionCancelledInChatHistory(int $userId, int $transactionId): void
    {
        ChatMessage::whereHas('conversation', fn ($q) => $q->where('user_id', $userId))
            ->where('role', 'assistant')
            ->chunkById(100, function ($messages) use ($transactionId) {
                foreach ($messages as $message) {
                    $content = $message->content ?? [];
                    $changed = false;

                    foreach ($content as &$component) {
                        if (($component['type'] ?? null) !== 'transaction_card') {
                            continue;
                        }

                        if ((int) ($component['transaction']['id'] ?? 0) !== $transactionId) {
                            continue;
                        }

                        $component['needs_wallet'] = false;
                        $component['transaction']['is_cancelled'] = true;
                        $component['transaction']['is_cleared'] = false;
                        $changed = true;
                    }

                    if ($changed) {
                        $message->forceFill(['content' => $content])->save();
                    }
                }
            });
    }

    /**
     * Perbarui data transaksi di riwayat chat setelah konfirmasi.
     * Ini memastikan apabila user scroll ke atas, bubble chat lama
     * juga menampilkan status confirmed dan notes yang sudah bersih.
     */
    private function markTransactionUpdatedInChatHistory(int $userId, TransactionLog $transaction): void
    {
        $transactionId = $transaction->id;

        $typeKey = TransactionIntent::typeKeyFromName($transaction->type?->name);

        ChatMessage::whereHas('conversation', fn ($q) => $q->where('user_id', $userId))
            ->where('role', 'assistant')
            ->chunkById(100, function ($messages) use ($transactionId, $transaction, $typeKey) {
                foreach ($messages as $message) {
                    $content = $message->content ?? [];
                    $changed = false;

                    foreach ($content as &$component) {
                        if (($component['type'] ?? null) !== 'transaction_card') {
                            continue;
                        }

                        if ((int) ($component['transaction']['id'] ?? 0) !== $transactionId) {
                            continue;
                        }

                        // Update status dan bersihkan notes di riwayat chat
                        $component['needs_wallet'] = false;
                        $component['transaction']['is_cleared'] = true;
                        $component['transaction']['is_cancelled'] = false;
                        $component['transaction']['notes'] = $transaction->notes;
                        $component['transaction']['type_key'] = $typeKey;
                        $component['transaction']['source_wallet'] = $transaction->sourceWallet?->name;
                        $component['transaction']['dest_wallet'] = $transaction->destinationWallet?->name;
                        $component['transaction']['amount_formatted'] = MoneyFormatter::rupiah($transaction->amount);
                        $changed = true;
                    }

                    if ($changed) {
                        $message->forceFill(['content' => $content])->save();
                    }
                }
            });
    }
}
