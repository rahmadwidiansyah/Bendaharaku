<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AiToolLog — jejak eksekusi tool assistant.
 *
 * params disanitasi sebelum disimpan; hasil besar tidak pernah ditulis.
 */
class AiToolLog extends Model
{
    protected $fillable = [
        'user_id',
        'conversation_id',
        'chat_message_id',
        'trace_id',
        'tool',
        'params',
        'status',
        'duration_ms',
        'result_rows',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'duration_ms' => 'integer',
            'result_rows' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
