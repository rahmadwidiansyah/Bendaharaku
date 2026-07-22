<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ChatMessage — satu pesan dalam sebuah Conversation.
 *
 * role: 'user' | 'assistant'
 * content: JSON array of ChatComponent objects (platform-agnostic)
 *   Format: [{ type: 'text', text: '...' }, { type: 'transaction_card', ... }]
 *
 * @property int $id
 * @property int $conversation_id
 * @property string $role 'user' | 'assistant'
 * @property array $content Array ChatComponent
 * @property string|null $raw_text Teks mentah user, atau ringkasan bot
 * @property array|null $metadata trace_id, provider, model, latency_ms, dsb
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class ChatMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'raw_text',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'metadata' => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeByUser($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeByBot($query)
    {
        return $query->where('role', 'assistant');
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isFromUser(): bool
    {
        return $this->role === 'user';
    }

    public function isFromBot(): bool
    {
        return $this->role === 'assistant';
    }

    /**
     * Ambil metadata value dengan fallback.
     */
    public function meta(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Teks pertama dari content (untuk preview/search).
     */
    public function textPreview(): string
    {
        foreach ($this->content ?? [] as $component) {
            if (($component['type'] ?? '') === 'text') {
                return (string) ($component['text'] ?? '');
            }
        }

        return $this->raw_text ?? '';
    }
}
