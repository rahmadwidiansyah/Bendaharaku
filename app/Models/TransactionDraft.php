<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk staging area hasil parsing AI Chat.
 *
 * Draft TIDAK pernah mempengaruhi saldo wallet atau laporan keuangan.
 * Transaksi keuangan nyata hanya dibuat di transaction_logs saat user konfirmasi.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $conversation_id
 * @property string|null $ai_provider
 * @property string|null $ai_model
 * @property string $draft_type            'single' | 'multi'
 * @property array $payload               Data hasil parsing AI
 * @property string $status               'pending' | 'confirmed' | 'cancelled' | 'expired'
 * @property array|null $confirmed_transaction_ids
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property float|null $ai_confidence
 * @property string|null $original_text
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class TransactionDraft extends Model
{
    protected $fillable = [
        'user_id',
        'conversation_id',
        'ai_provider',
        'ai_model',
        'draft_type',
        'payload',
        'status',
        'confirmed_transaction_ids',
        'expires_at',
        'ai_confidence',
        'original_text',
    ];

    protected $casts = [
        'payload'                   => 'array',
        'confirmed_transaction_ids' => 'array',
        'ai_confidence'             => 'float',
        'expires_at'                => 'datetime',
    ];

    // ── Relasi ────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Apakah draft masih aktif (pending & belum expired).
     */
    public function isPending(): bool
    {
        return $this->status === 'pending'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Apakah draft sudah dikonfirmasi.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Apakah draft sudah dibatalkan.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
