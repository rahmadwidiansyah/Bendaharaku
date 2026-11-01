<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;

/**
 * ConversationHistoryBuilder — ringkasan 6 pesan terakhir untuk agent loop.
 *
 * Hanya membaca conversation milik user yang sedang terautentikasi.
 * Pesan saat ini (yang sedang diproses) selalu dikecualikan agar tidak
 * terduplikasi di messages[].
 */
final class ConversationHistoryBuilder
{
    /**
     * @return array<int, array{role:string,text:string}>
     */
    public function build(
        User $user,
        int|string|null $conversationId,
        ?int $excludeMessageId = null,
        string $currentText = '',
        int $limit = 6,
    ): array {
        if ($conversationId === null || $conversationId === '') {
            return [];
        }

        $conversation = Conversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->first();

        if (! $conversation) {
            return [];
        }

        $messages = ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('status', 'completed')
            ->when($excludeMessageId !== null, fn ($query) => $query->where('id', '!=', $excludeMessageId))
            ->orderByDesc('id')
            ->limit($limit + 1)
            ->get()
            ->reverse()
            ->values();

        // Tanpa id eksplisit (mis. Telegram), buang pesan user terakhir yang
        // teksnya sama dengan pesan saat ini.
        if ($excludeMessageId === null && $currentText !== '') {
            $lastIndex = $messages->count() - 1;
            if ($lastIndex >= 0) {
                $last = $messages[$lastIndex];
                if ($last->role === 'user' && trim((string) $this->textFor($last)) === trim($currentText)) {
                    $messages->forget($lastIndex);
                }
            }
        }

        return $messages
            ->take(-$limit)
            ->map(fn (ChatMessage $message) => [
                'role' => $message->role === 'assistant' ? 'assistant' : 'user',
                'text' => mb_substr($this->textFor($message), 0, 500),
            ])
            ->values()
            ->all();
    }

    private function textFor(ChatMessage $message): string
    {
        if (! blank($message->raw_text)) {
            return trim((string) $message->raw_text);
        }

        $parts = [];
        foreach (($message->content ?? []) as $component) {
            if (($component['type'] ?? null) === 'text' && isset($component['text'])) {
                $parts[] = (string) $component['text'];
            }
        }

        return trim(implode("\n", $parts));
    }
}
