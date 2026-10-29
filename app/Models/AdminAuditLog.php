<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'old_values',
        'new_values',
        'metadata',
        'success',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'success' => 'boolean',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        User $admin,
        string $action,
        string $targetType,
        ?int $targetId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        bool $success = true
    ): self {
        return self::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'success' => $success,
        ]);
    }
}
