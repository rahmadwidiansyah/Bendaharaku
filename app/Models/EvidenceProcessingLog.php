<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EvidenceProcessingLog — menyimpan riwayat setiap perpindahan status evidence.
 *
 * Setiap perubahan stage mencatat:
 * - stage: nama stage (UPLOAD, QUEUE, OCR, CLASSIFY, PARSE, RESOLVE, COMMIT)
 * - status_before: status sebelum perubahan
 * - status_after: status sesudah perubahan
 * - duration_ms: durasi proses stage ini (nullable)
 * - message: pesan error/sukses (nullable)
 * - metadata: JSON tambahan (engine version, dll)
 *
 * @property int $id
 * @property int $evidence_id
 * @property string $stage
 * @property string|null $status_before
 * @property string $status_after
 * @property int|null $duration_ms
 * @property string|null $message
 * @property array|null $metadata
 * @property-read Evidence $evidence
 */
class EvidenceProcessingLog extends Model
{
    protected $fillable = [
        'evidence_id',
        'stage',
        'status_before',
        'status_after',
        'duration_ms',
        'message',
        'metadata',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'metadata' => 'array',
    ];

    // ── Relations ─────────────────────────────────────────────────────

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }
}
