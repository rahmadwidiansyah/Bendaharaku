<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Conversation — satu sesi percakapan antara user dan bot.
 *
 * Phase awal: satu active conversation per user.
 * Future: multiple conversations dengan title, archive, dsb.
 *
 * @property int         $id
 * @property int         $user_id
 * @property string|null $title
 * @property bool        $is_active
 * @property \Carbon\Carbon|null $archived_at
 * @property array|null  $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'is_active',
        'archived_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'archived_at' => 'datetime',
            'metadata'    => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->latest()->limit(1);
    }

    // ── Scopes ───────────────────────────────────────────────────

    /**
     * Hanya conversation yang aktif (tidak di-archive, tidak di-delete).
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('archived_at');
    }

    /**
     * Conversation yang di-archive.
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * Archive conversation ini (belum di-delete, tapi tidak aktif lagi).
     */
    public function archive(): void
    {
        $this->update([
            'is_active'   => false,
            'archived_at' => now(),
        ]);
    }

    /**
     * Restore dari archive.
     */
    public function unarchive(): void
    {
        $this->update([
            'is_active'   => true,
            'archived_at' => null,
        ]);
    }

    /**
     * Jumlah pesan dalam conversation ini.
     */
    public function messageCount(): int
    {
        return $this->messages()->count();
    }
}
