<?php

declare(strict_types=1);

namespace App\Chat\Adapters;

use App\Chat\ChatApplicationService;
use App\Chat\DTOs\ChatContext;
use App\Chat\Formatters\WebFormatter;
use App\Chat\Services\CommandRouter;
use App\Enums\ChatPlatform;
use App\Enums\TransactionIntent;
use App\Jobs\EvidenceLlmGroupingJob;
use App\Jobs\ProcessChatMessageJob;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Evidence;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Models\User;
use App\Support\MoneyFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * WebAdapter — Adapter untuk platform Web Chat.
 *
 * Tanggung jawab:
 * 1. Menerima HTTP request data (teks, user, conversation_id)
 * 2. Resolve atau buat Conversation aktif untuk user
 * 3. Simpan pesan user ke chat_messages
 * 4. Buat pesan bot pending + dispatch ProcessChatMessageJob (async)
 * 5. Format data untuk HTTP response
 *
 * Tidak ada AI logic, tidak ada business rule di sini.
 * Proses AI berjalan di background job agar tidak batal saat user pindah halaman.
 * Tidak ada Telegram-specific code.
 */
class WebAdapter
{
    public function __construct(
        private readonly ChatApplicationService $chatService,
        private readonly WebFormatter $formatter,
        private readonly CommandRouter $commandRouter,
    ) {}

    /**
     * Terima satu pesan dari Web Chat (async).
     *
     * Pesan user + pesan bot pending disimpan langsung, lalu proses AI
     * di-delegate ke ProcessChatMessageJob. Response dikembalikan segera.
     *
     * @param  User  $user  User yang mengirim pesan
     * @param  string  $rawMessage  Teks mentah dari user
     * @param  int|null  $conversationId  ID conversation (null = gunakan/buat active)
     * @return array JSON-ready response
     */
    public function enqueueMessage(User $user, string $rawMessage, ?int $conversationId = null, ?string $evidenceUuid = null): array
    {
        // 1. Resolve conversation — selalu ada, tidak pernah null
        $conversation = $this->resolveConversation($user, $conversationId);

        // 2. Build user content — support image evidence (WA-style: foto + caption)
        $userContent = [];
        $evidence = null;
        $imageUrl = null;

        if ($evidenceUuid) {
            $evidence = Evidence::where('uuid', $evidenceUuid)
                ->where('user_id', $user->id)
                ->first();

            if ($evidence) {
                // URL via Evidence model (akan jadi route chat.evidence.image setelah fix)
                try {
                    $imageUrl = $evidence->url;
                } catch (\Throwable $e) {
                    $imageUrl = null;
                }

                $userContent[] = [
                    'type' => 'image',
                    'imageUrl' => $imageUrl,
                    'image_url' => $imageUrl, // alias untuk kompatibilitas
                    'evidenceUuid' => $evidence->uuid,
                    'evidence_uuid' => $evidence->uuid,
                    'evidenceStatus' => $evidence->status->value,
                    'evidence_status' => $evidence->status->value,
                    'committed' => false,
                ];
            }
        }

        // Tambahkan teks jika ada dan bukan placeholder "[Evidence]"
        $trimmed = trim($rawMessage);
        if ($trimmed !== '' && $trimmed !== '[Evidence]') {
            $userContent[] = ['type' => 'text', 'text' => $trimmed];
        } elseif (empty($userContent)) {
            // Fallback: jika tidak ada image dan teks kosong (tidak seharusnya), simpan placeholder
            $userContent[] = ['type' => 'text', 'text' => $trimmed !== '' ? $trimmed : '[Evidence]'];
        }

        // Metadata: simpan evidence_uuid untuk tracking
        $userMetadata = null;
        if ($evidence) {
            $userMetadata = ['evidence_uuid' => $evidence->uuid];
        }

        // 2b. Simpan pesan user ke DB
        $userMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userContent,
            'raw_text' => $rawMessage,
            'metadata' => $userMetadata,
            'status' => 'completed',
        ]);

        // 3. Jika ada evidence (foto struk) → LLM primary grouping, tampil di bubble (bukan sheet)
        //    Caption "Tunai"/"BCA" jadi wallet hint prioritas 1, OCR label wallet prioritas 2, else QuickWalletPicker
        //    Nama barang → notes = description (samakan) biar multi kategori langsung kelompok
        if ($evidence) {
            $captionHint = trim($rawMessage) !== '' && trim($rawMessage) !== '[Evidence]' ? trim($rawMessage) : '';
            $botMessage = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => [],
                'raw_text' => null,
                'metadata' => [
                    'evidence_uuid' => $evidence->uuid,
                    'caption_hint' => $captionHint,
                    'intent' => 'evidence_grouping',
                    'success' => true,
                ],
                'status' => 'pending',
            ]);

            EvidenceLlmGroupingJob::dispatch($evidence->id, $user->id, $botMessage->id, $captionHint);

            return [
                'success' => true,
                'queued' => true,
                'conversation_id' => $conversation->id,
                'user_message' => [
                    'id' => $userMessage->id,
                    'role' => 'user',
                    'status' => 'completed',
                    'content' => $userMessage->content,
                    'metadata' => $userMessage->metadata ?? [],
                    'created_at' => $userMessage->created_at->toIso8601String(),
                ],
                'bot_message' => [
                    'id' => $botMessage->id,
                    'role' => 'assistant',
                    'status' => 'pending',
                    'content' => [],
                    'metadata' => $botMessage->metadata ?? [],
                    'created_at' => $botMessage->created_at->toIso8601String(),
                ],
            ];
        }

        // 3a. Filter struk: jika pesan seperti "punyaku cuma ayam goreng dan es jeruk" setelah evidence 5 item
        //     → filter bubble sebelumnya jadi hanya 2 item yang disebutkan (biar ga perlu edit manual 3 lainnya)
        $filterResponse = $this->tryHandleEvidenceFilter($user, $conversation, $rawMessage, $userMessage);
        if ($filterResponse !== null) {
            return $filterResponse;
        }

        // 3b. Coba handle sebagai command langsung (sync, tanpa queue).
        //    Command seperti /help, /saldo, /transaksi tidak butuh AI/LLM
        //    sehingga aman diproses synchronous di HTTP request.
        $startTime = microtime(true);
        $locale = $user->locale ?? 'id';
        $latency = (int) round((microtime(true) - $startTime) * 1000);
        $metadata = [
            'platform' => ChatPlatform::Web->value,
            'latency_ms' => $latency,
        ];

        $context = ChatContext::make(
            platform: ChatPlatform::Web,
            conversationId: (string) $conversation->id,
            locale: $locale,
            timezone: $user->timezone ?? 'Asia/Jakarta',
            sessionId: (string) $conversation->id,
            metadata: ['web_message_id' => $userMessage->id],
        );

        $commandResponse = $this->commandRouter->route($rawMessage, $user, $context, $startTime);

        if ($commandResponse !== null) {
            // Command dikenali — proses sync, langsung simpan sebagai completed
            $formatted = $this->formatter->format($commandResponse, $context);
            $latency = (int) round((microtime(true) - $startTime) * 1000);

            $botMessage = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $formatted['components'],
                'raw_text' => self::extractTextFromComponents($formatted['components']),
                'metadata' => array_merge($commandResponse->metadata, [
                    'intent' => $commandResponse->intent->value,
                    'success' => $commandResponse->success,
                    'latency_ms' => $latency,
                    'raw_prompt' => $rawMessage,
                ]),
                'status' => 'completed',
            ]);

            return [
                'success' => true,
                'queued' => false,
                'conversation_id' => $conversation->id,
                'user_message' => [
                    'id' => $userMessage->id,
                    'role' => 'user',
                    'status' => 'completed',
                    'content' => $userMessage->content,
                    'metadata' => $userMessage->metadata ?? [],
                    'created_at' => $userMessage->created_at->toIso8601String(),
                ],
                'bot_message' => [
                    'id' => $botMessage->id,
                    'role' => 'assistant',
                    'status' => 'completed',
                    'content' => $formatted['components'],
                    'metadata' => $botMessage->metadata ?? [],
                    'created_at' => $botMessage->created_at->toIso8601String(),
                ],
            ];
        }

        // 4. Bukan command — proses AI via background queue
        $botMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => [],
            'raw_text' => null,
            'metadata' => ['web_message_id' => $userMessage->id],
            'status' => 'pending',
        ]);

        ProcessChatMessageJob::dispatch($user->id, $conversation->id, $userMessage->id, $botMessage->id);

        // 5. Return segera — frontend polling status pesan bot
        return [
            'success' => true,
            'queued' => true,
            'conversation_id' => $conversation->id,
            'user_message' => [
                'id' => $userMessage->id,
                'role' => 'user',
                'status' => 'completed',
                'content' => $userMessage->content,
                'metadata' => $userMessage->metadata ?? [],
                'created_at' => $userMessage->created_at->toIso8601String(),
            ],
            'bot_message' => [
                'id' => $botMessage->id,
                'role' => 'assistant',
                'status' => 'pending',
                'content' => [],
                'metadata' => [],
                'created_at' => $botMessage->created_at->toIso8601String(),
            ],
        ];
    }

    /**
     * Format satu pesan untuk client (endpoint status & riwayat).
     */
    public function formatMessageForClient(ChatMessage $msg): array
    {
        $content = $msg->content ?? [];

        if ($msg->status === 'failed' && $msg->error_message && empty($content)) {
            $content = [[
                'type' => 'error',
                'message' => $msg->error_message,
                'severity' => 'error',
            ]];
        }

        return [
            'id' => $msg->id,
            'role' => $msg->role,
            'status' => $msg->status ?? 'completed',
            'content' => $content,
            'metadata' => $msg->metadata ?? [],
            'created_at' => $msg->created_at->toIso8601String(),
        ];
    }

    /**
     * Ambil semua pesan sejak tanggal tertentu (untuk initial load 7 hari).
     * Diurutkan ascending (chronological) untuk render chat.
     */
    public function getHistorySince(Conversation $conversation, Carbon $since): array
    {
        $messages = $conversation->messages()
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->get();

        return $this->syncTransactionCardsWithDb($messages, $conversation->user_id)
            ->map(fn (ChatMessage $msg) => $this->formatMessageForClient($msg))
            ->all();
    }

    /**
     * Ambil riwayat pesan dari conversation (untuk pagination ke belakang).
     *
     * @param  int  $limit  Jumlah pesan terbaru
     * @param  int|null  $before  Cursor ID (untuk pagination ke belakang)
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

        return $this->syncTransactionCardsWithDb($messages, $conversation->user_id)
            ->map(fn (ChatMessage $msg) => $this->formatMessageForClient($msg))
            ->all();
    }

    // ── Private ───────────────────────────────────────────────────

    private function syncTransactionCardsWithDb($messages, int $userId)
    {
        // 1. Kumpulkan semua transaction ID (non-draft) dan draft ID dari messages
        $transactionIds = [];
        $draftIds = [];

        foreach ($messages as $message) {
            foreach ($message->content ?? [] as $component) {
                if (($component['type'] ?? null) !== 'transaction_card') {
                    continue;
                }
                $isDraft = (bool) ($component['is_draft'] ?? ($component['transaction']['is_draft'] ?? false));
                $id = (int) ($component['transaction']['id'] ?? 0);
                if ($id > 0) {
                    if ($isDraft) {
                        $draftIds[] = $id;
                    } else {
                        $transactionIds[] = $id;
                    }
                }
            }
        }

        $transactionIds = array_values(array_unique($transactionIds));
        $draftIds = array_values(array_unique($draftIds));

        // 2. Query data dari DB
        $existingTransactions = collect();
        if (! empty($transactionIds)) {
            $existingTransactions = TransactionLog::query()
                ->where('user_id', $userId)
                ->whereIn('id', $transactionIds)
                ->get()
                ->keyBy('id');
        }

        $existingDrafts = collect();
        if (! empty($draftIds)) {
            $existingDrafts = TransactionDraft::query()
                ->where('user_id', $userId)
                ->whereIn('id', $draftIds)
                ->get()
                ->keyBy('id');
        }

        // 3. Update messages content
        return $messages->map(function (ChatMessage $message) use ($existingTransactions, $existingDrafts) {
            $content = $message->content ?? [];
            $changed = false;

            foreach ($content as &$component) {
                if (($component['type'] ?? null) !== 'transaction_card') {
                    continue;
                }

                $isDraft = (bool) ($component['is_draft'] ?? ($component['transaction']['is_draft'] ?? false));
                $id = (int) ($component['transaction']['id'] ?? 0);

                if (! $id) {
                    continue;
                }

                if ($isDraft) {
                    $draft = $existingDrafts->get($id);
                    if (! $draft) {
                        // Draft tidak ditemukan → tandai sebagai cancelled
                        $component['needs_wallet'] = false;
                        $component['transaction']['is_cancelled'] = true;
                        $component['transaction']['is_cleared'] = false;
                        $changed = true;
                    } else {
                        // Draft ditemukan. Cek statusnya
                        if ($draft->status === 'confirmed') {
                            // Draft sudah dikonfirmasi!
                            // Dapatkan ID transaksi log yang baru
                            $confirmedLogId = ! empty($draft->confirmed_transaction_ids) ? (int) $draft->confirmed_transaction_ids[0] : null;
                            $transaction = $confirmedLogId ? TransactionLog::with(['category', 'sourceWallet', 'destinationWallet', 'type'])->find($confirmedLogId) : null;

                            if ($transaction) {
                                $typeKey = TransactionIntent::typeKeyFromName($transaction->type?->name);

                                // Ubah komponen draft menjadi confirmed transaction card
                                $component['needs_wallet'] = false;
                                $component['is_draft'] = false;
                                $component['transaction']['id'] = $transaction->id;
                                $component['transaction']['draft_id'] = null;
                                $component['transaction']['is_draft'] = false;
                                $component['transaction']['is_cleared'] = true;
                                $component['transaction']['is_cancelled'] = false;
                                $component['transaction']['notes'] = $transaction->notes;
                                $component['transaction']['type_key'] = $typeKey;
                                $component['transaction']['source_wallet'] = $transaction->sourceWallet?->name;
                                $component['transaction']['dest_wallet'] = $transaction->destinationWallet?->name;
                                $component['transaction']['amount_formatted'] = MoneyFormatter::rupiah($transaction->amount);
                                $component['transaction']['reference_number'] = $transaction->reference_number;
                                $changed = true;
                            } else {
                                // Jika log tidak ditemukan karena alasan tertentu, set cancelled
                                $component['needs_wallet'] = false;
                                $component['transaction']['is_cancelled'] = true;
                                $component['transaction']['is_cleared'] = false;
                                $changed = true;
                            }
                        } elseif (in_array($draft->status, ['cancelled', 'expired'])) {
                            // Draft dibatalkan atau expired
                            $component['needs_wallet'] = false;
                            $component['transaction']['is_cancelled'] = true;
                            $component['transaction']['is_cleared'] = false;
                            $changed = true;
                        }
                    }
                } else {
                    // Normal transaction card
                    $trx = $existingTransactions->get($id);
                    if (! $trx) {
                        // Transaksi tidak ditemukan (dihapus dari DB)
                        $component['needs_wallet'] = false;
                        $component['transaction']['is_cancelled'] = true;
                        $component['transaction']['is_cleared'] = false;
                        $changed = true;
                    } else {
                        // Update data transaksi dari DB (balance, wallet name, dll)
                        $typeKey = TransactionIntent::typeKeyFromName($trx->type?->name);
                        $component['transaction']['id'] = $trx->id;
                        $component['transaction']['is_cleared'] = $trx->is_cleared;
                        $component['transaction']['is_cancelled'] = false;
                        $component['transaction']['reference_number'] = $trx->reference_number;
                        $component['transaction']['amount'] = $trx->amount;
                        $component['transaction']['amount_formatted'] = MoneyFormatter::rupiah($trx->amount);
                        $component['transaction']['type_key'] = $typeKey;
                        $component['transaction']['source_wallet'] = $trx->sourceWallet?->name;
                        $component['transaction']['dest_wallet'] = $trx->destinationWallet?->name;
                        $component['transaction']['notes'] = $trx->notes;
                        $component['transaction']['subject'] = $trx->subject;
                        $component['transaction']['category'] = $trx->category?->category_name;
                        $component['needs_wallet'] = false;
                        $changed = true;
                    }
                }
            }
            unset($component);

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
    /**
     * Ekstrak ringkasan teks dari komponen pesan (dipakai juga oleh ProcessChatMessageJob).
     */
    public static function extractTextFromComponents(array $components): string
    {
        $texts = [];
        foreach ($components as $component) {
            $type = $component['type'] ?? '';
            if ($type === 'text') {
                $texts[] = $component['text'] ?? '';
            } elseif ($type === 'transaction_card') {
                $parts = array_filter([
                    $component['transaction']['category'] ?? null,
                    $component['transaction']['amount_formatted'] ?? null,
                    $component['transaction']['subject'] ?? null,
                ]);
                $texts[] = implode(' — ', $parts);
            } elseif ($type === 'summary_card') {
                $texts[] = $component['label'] ?? '';
            } elseif ($type === 'error') {
                $texts[] = $component['message'] ?? '';
            }
        }

        return implode(' | ', array_filter($texts));
    }

    private function tryHandleEvidenceFilter(User $user, Conversation $conversation, string $rawMessage, ChatMessage $userMessage): ?array
    {
        $lower = mb_strtolower(trim($rawMessage));
        // Deteksi filter: harus mengandung kata filter + minimal 3 char
        $isFilter = str_contains($lower, 'punyaku') || str_contains($lower, 'cuma') || str_contains($lower, 'hanya') || str_contains($lower, 'punya saya') || str_contains($lower, 'milikku') || str_contains($lower, 'yang saya');
        if (!$isFilter || mb_strlen($lower) < 5) {
            return null;
        }

        // Cari bot message terakhir yang evidence_grouping (LLM struk)
        $lastBot = ChatMessage::where('conversation_id', $conversation->id)
            ->where('role', 'assistant')
            ->where('status', 'completed')
            ->whereJsonContains('metadata->intent', 'evidence_grouping')
            ->latest('id')
            ->first();

        // Fallback: cari bot message terakhir yang multiTransaction (jika intent berbeda)
        if (!$lastBot) {
            $lastBot = ChatMessage::where('conversation_id', $conversation->id)
                ->where('role', 'assistant')
                ->where('status', 'completed')
                ->latest('id')
                ->first();
            if (!$lastBot || empty($lastBot->content)) return null;
            $hasCards = collect($lastBot->content)->contains(fn($c) => ($c['type'] ?? '') === 'transaction_card');
            if (!$hasCards) return null;
        }

        $prevCards = collect($lastBot->content)->filter(fn($c) => ($c['type'] ?? '') === 'transaction_card')->values();
        if ($prevCards->isEmpty()) return null;

        // Filter: keep card jika notes/category-nya disebut di filter text
        $filtered = $prevCards->filter(function ($card) use ($lower) {
            $notes = mb_strtolower($card['transaction']['notes'] ?? $card['transaction']['category'] ?? '');
            $category = mb_strtolower($card['transaction']['category'] ?? '');
            // Cek apakah notes atau category muncul di filter text
            if ($notes !== '' && str_contains($lower, $notes)) return true;
            // Cek per kata (mis. "ayam goreng" → cek "ayam" dan "goreng")
            $words = preg_split('/\s+/', $notes, -1, PREG_SPLIT_NO_EMPTY);
            $matchedWords = 0;
            foreach ($words as $w) {
                if (mb_strlen($w) >= 3 && str_contains($lower, $w)) $matchedWords++;
            }
            if ($matchedWords >= 1 && count($words) <= 2) return true;
            if ($matchedWords >= 2) return true;
            if ($category !== '' && str_contains($lower, $category)) return true;
            return false;
        })->values();

        // Jika tidak ada yang match atau semua match → bukan filter valid, biarkan flow normal
        if ($filtered->isEmpty() || $filtered->count() === $prevCards->count()) {
            return null;
        }

        // Buat summary baru untuk filtered
        $total = $filtered->count();
        $summary = collect($lastBot->content)->firstWhere('type', 'summary_card');
        $newSummary = $summary ? [
            'type' => 'summary_card',
            'total' => $total,
            'success' => $total,
            'failed' => 0,
            'confidence' => $summary['confidence'] ?? 0.9,
            'label' => "✅ *{$total} transaksi terpilih* (dari {$prevCards->count()})",
            'all_success' => true,
            'all_failed' => false,
        ] : null;

        $newComponents = [];
        if ($newSummary) $newComponents[] = $newSummary;
        // Re-index filtered cards dengan index baru 1..n
        foreach ($filtered as $idx => $card) {
            $card['index'] = $idx + 1;
            $newComponents[] = $card;
        }

        $botMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $newComponents,
            'raw_text' => self::extractTextFromComponents($newComponents),
            'metadata' => [
                'intent' => 'evidence_filter',
                'success' => true,
                'filtered_from' => $lastBot->id,
                'original_count' => $prevCards->count(),
                'filtered_count' => $total,
            ],
            'status' => 'completed',
        ]);

        Log::info('Evidence filter applied', [
            'conversation_id' => $conversation->id,
            'filter_text' => $rawMessage,
            'original' => $prevCards->count(),
            'filtered' => $total,
        ]);

        return [
            'success' => true,
            'queued' => false,
            'conversation_id' => $conversation->id,
            'user_message' => [
                'id' => $userMessage->id,
                'role' => 'user',
                'status' => 'completed',
                'content' => $userMessage->content,
                'metadata' => $userMessage->metadata ?? [],
                'created_at' => $userMessage->created_at->toIso8601String(),
            ],
            'bot_message' => [
                'id' => $botMessage->id,
                'role' => 'assistant',
                'status' => 'completed',
                'content' => $botMessage->content,
                'metadata' => $botMessage->metadata ?? [],
                'created_at' => $botMessage->created_at->toIso8601String(),
            ],
        ];
    }

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
            'user_id' => $user->id,
            'title' => null,
            'is_active' => true,
        ]);
    }
}
