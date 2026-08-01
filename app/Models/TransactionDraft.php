<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WalletSide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
 * @property string $draft_type 'single' | 'multi'
 * @property array $payload Data hasil parsing AI
 * @property string|null $missing_wallet_side SOURCE | DESTINATION | NONE | BOTH
 * @property string $status 'pending' | 'confirmed' | 'cancelled' | 'expired'
 * @property array|null $confirmed_transaction_ids
 * @property Carbon|null $expires_at
 * @property float|null $ai_confidence
 * @property string|null $original_text
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TransactionDraft extends Model
{
    public const string STATUS_PENDING = 'pending';

    public const string STATUS_CONFIRMED = 'confirmed';

    public const string STATUS_CANCELLED = 'cancelled';

    public const string STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'ai_provider',
        'ai_model',
        'draft_type',
        'payload',
        'missing_wallet_side',
        'status',
        'confirmed_transaction_ids',
        'expires_at',
        'ai_confidence',
        'original_text',
    ];

    protected $casts = [
        'payload' => 'array',
        'missing_wallet_side' => 'string',
        'confirmed_transaction_ids' => 'array',
        'ai_confidence' => 'float',
        'expires_at' => 'datetime',
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
        return $this->status === self::STATUS_PENDING
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isMissingSource(): bool
    {
        return $this->missing_wallet_side === WalletSide::Source->value
            || $this->missing_wallet_side === WalletSide::Both->value;
    }

    public function isMissingDestination(): bool
    {
        return $this->missing_wallet_side === WalletSide::Destination->value
            || $this->missing_wallet_side === WalletSide::Both->value;
    }
}
