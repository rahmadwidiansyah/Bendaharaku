<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Chat\Adapters\WebAdapter;
use App\Chat\ChatCommandRegistry;
use App\Actions\ProcessTransactionAction;
use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Models\TransactionLog;
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

        // Load pesan 7 hari terakhir (ambil +1 untuk deteksi hasMore)
        $messages = [];
        $hasMore  = false;
        if ($conversation) {
            $sevenDaysAgo = now()->subDays(7)->startOfDay();
            $raw = $this->adapter->getHistorySince($conversation, $sevenDaysAgo);

            // hasMore = true jika ada pesan lebih lama dari 7 hari yang lalu
            $hasMore = $conversation->messages()
                ->where('created_at', '<', $sevenDaysAgo)
                ->exists();

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
            // Fallback terakhir — hanya terjadi jika DB down atau exception
            // sebelum conversation bisa di-resolve (sangat jarang).
            // Tetap kirim conversation_id agar frontend tidak reset ke null.
            Log::error('WebChatController: sendMessage fatal error', [
                'user_id'         => $request->user()->id,
                'conversation_id' => $validated['conversation_id'] ?? null,
                'error'           => $e->getMessage(),
            ]);

            // Coba ambil conversation_id yang valid dari DB sebagai fallback
            $fallbackConvId = $validated['conversation_id'] ?? null;
            if (!$fallbackConvId) {
                $fallbackConv = \App\Models\Conversation::where('user_id', $request->user()->id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->latest()
                    ->value('id');
                $fallbackConvId = $fallbackConv;
            }

            return response()->json([
                'success'         => false,
                'conversation_id' => $fallbackConvId,
                'bot_message'     => [
                    'id'         => null,
                    'role'       => 'assistant',
                    'content'    => [[
                        'type'     => 'error',
                        'message'  => __('chat.error.system'),
                        'severity' => 'error',
                    ]],
                    'metadata'   => ['error' => true],
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

        if (!empty($walletIds)) {
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
            'wallets' => $wallets->map(fn($w) => [
                'id'   => $w->id,
                'name' => $w->name,
            ])->values(),
        ]);
    }

    /**
     * PATCH /chat/transaction/{id}/wallet
     * Assign wallet ke draft transaksi dan konfirmasi (is_cleared = true).
     * Mutasi saldo wallet dilakukan di sini.
     */
    public function assignWallet(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        // Pastikan transaksi milik user ini dan masih draft
        $transaction = $user->transactionLogs()
            ->with(['sourceWallet', 'destinationWallet', 'category', 'type'])
            ->where('is_cleared', false)
            ->findOrFail($id);

        // Pastikan wallet milik user
        $wallet = $user->wallets()
            ->where('group_type', '!=', 'System')
            ->findOrFail($validated['wallet_id']);

        // Resolve tipe transaksi
        // Untuk expense: source = wallet pilihan user, destination = merchant (system)
        // Untuk income:  source = external (system), destination = wallet pilihan user
        $typeKey = strtolower($transaction->type->name ?? 'expense');

        DB::transaction(function () use ($transaction, $wallet, $user, $typeKey) {
            if ($typeKey === 'expense') {
                $transaction->source_wallet_id = $wallet->id;
                // Mutasi saldo: kurangi dari wallet
                $balanceBefore = $wallet->balance;
                if (!$user->allow_negative_balance && $wallet->balance < $transaction->amount) {
                    throw new \InvalidArgumentException('Saldo tidak mencukupi.');
                }
                $wallet->decrement('balance', $transaction->amount);
                $transaction->balance_before = $balanceBefore;
                $transaction->balance_after  = $wallet->fresh()->balance;
            } elseif ($typeKey === 'income') {
                $transaction->destination_wallet_id = $wallet->id;
                $balanceBefore = $wallet->balance;
                $wallet->increment('balance', $transaction->amount);
                $transaction->balance_before = $balanceBefore;
                $transaction->balance_after  = $wallet->fresh()->balance;
            } else {
                // debt/receivable/transfer — assign ke source
                $transaction->source_wallet_id = $wallet->id;
            }
            $transaction->is_cleared = true;
            $transaction->save();
        });

        $transaction->refresh()->load(['sourceWallet', 'destinationWallet', 'category', 'type']);
        $this->markTransactionUpdatedInChatHistory($user->id, $transaction);

        $typeKey = match (strtolower($transaction->type->name ?? '')) {
            'income'   => 'income',
            'expense'  => 'expense',
            'transfer' => 'transfer',
            default    => 'other',
        };

        return response()->json([
            'success'     => true,
            'transaction' => [
                'id'               => $transaction->id,
                'reference_number' => $transaction->reference_number,
                'is_cleared'       => $transaction->is_cleared,
                'is_cancelled'     => false,
                'type_key'         => $typeKey,
                'amount_formatted' => \App\Support\MoneyFormatter::rupiah($transaction->amount),
                'source_wallet'    => $transaction->sourceWallet?->name,
                'dest_wallet'      => $transaction->destinationWallet?->name,
                'category'         => $transaction->category?->category_name,
                'date'             => $transaction->date?->toDateString(),
                'notes'            => $transaction->notes,
                'created_at'       => $transaction->created_at?->toIso8601String(),
            ],
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

        if (!$transaction) {
            return response()->json([
                'exists'      => false,
                'transaction' => [
                    'id'           => $id,
                    'is_cancelled' => true,
                    'is_cleared'   => false,
                ],
            ]);
        }

        return response()->json([
            'exists'      => true,
            'transaction' => $this->formatTransactionForChat($transaction),
        ]);
    }

    /**
     * PATCH /chat/transaction/{id}/confirm
     * Konfirmasi draft yang wallet-nya sudah lengkap.
     */
    public function confirmTransaction(Request $request, int $id, ProcessTransactionAction $action): JsonResponse
    {
        $transaction = $request->user()
            ->transactionLogs()
            ->with(['sourceWallet', 'destinationWallet', 'category', 'type'])
            ->find($id);

        if (!$transaction) {
            return response()->json([
                'success'     => false,
                'message'     => 'Transaksi sudah batal.',
                'transaction' => [
                    'id'           => $id,
                    'is_cancelled' => true,
                    'is_cleared'   => false,
                ],
            ], 404);
        }

        if (!$transaction->is_cleared) {
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

        return response()->json([
            'success'     => true,
            'transaction' => $this->formatTransactionForChat($transaction),
        ]);
    }

    /**
     * DELETE /chat/transaction/{id}/cancel
     * Batal/hapus transaksi dari chat, lalu simpan label batal di riwayat chat.
     */
    public function cancelTransaction(Request $request, int $id, ProcessTransactionAction $action): JsonResponse
    {
        $transaction = $request->user()
            ->transactionLogs()
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

        $this->markTransactionCancelledInChatHistory($request->user()->id, $id);

        return response()->json([
            'success'     => true,
            'transaction' => [
                'id'           => $id,
                'is_cancelled' => true,
                'is_cleared'   => false,
            ],
        ]);
    }

    private function formatTransactionForChat(TransactionLog $transaction): array
    {
        $typeKey = match (strtolower($transaction->type?->name ?? '')) {
            'income'             => 'income',
            'expense'            => 'expense',
            'transfer'           => 'transfer',
            'debt', 'receivable' => 'debt',
            default              => 'other',
        };

        return [
            'id'               => $transaction->id,
            'reference_number' => $transaction->reference_number,
            'amount'           => $transaction->amount,
            'amount_formatted' => MoneyFormatter::rupiah($transaction->amount),
            'is_cleared'       => (bool) $transaction->is_cleared,
            'is_cancelled'     => false,
            'needs_wallet'     => !$transaction->is_cleared && $transaction->sourceWallet?->group_type === 'System',
            'type_key'         => $typeKey,
            'category'         => $transaction->category?->category_name,
            'source_wallet'    => $transaction->sourceWallet?->name,
            'dest_wallet'      => $transaction->destinationWallet?->name,
            'subject'          => $transaction->subject,
            'notes'            => $transaction->notes,
            'date'             => $transaction->date?->toDateString(),
            'created_at'       => $transaction->created_at?->toIso8601String(),
        ];
    }

    private function markTransactionCancelledInChatHistory(int $userId, int $transactionId): void
    {
        ChatMessage::whereHas('conversation', fn($q) => $q->where('user_id', $userId))
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
}
