<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\EvidenceStatus;
use App\Evidence\DTO\EvidenceData;
use App\Evidence\DTO\TransactionDraft;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Evidence — metadata file bukti (gambar) yang di-upload user.
 *
 * File fisik disimpan di storage/app/private/evidence/{user_id}/.
 * Model ini HANYA menyimpan metadata, bukan konten file.
 *
 * Lifecycle pipeline:
 *   UPLOADED → QUEUED → PROCESSING → OCR_COMPLETED → CLASSIFIED → PARSED → RESOLVED → READY → COMPLETED
 *                                                        ↘ FAILED
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $original_name
 * @property string $stored_name
 * @property string $mime_type
 * @property string $extension
 * @property int $size
 * @property string $disk
 * @property string $path
 * @property EvidenceStatus $status
 * @property string $source CHAT_UPLOAD|TELEGRAM|API
 * @property Carbon|null $processing_started_at
 * @property Carbon|null $processing_finished_at
 * @property string|null $error_message
 * @property int $retry_count
 * @property Carbon|null $last_processed_at
 * @property string|null $ocr_text
 * @property string|null $ocr_engine
 * @property int|null $ocr_duration_ms
 * @property string|null $ocr_version
 * @property DocumentType|null $document_type
 * @property string|null $classifier_engine
 * @property string|null $classifier_version
 * @property float|null $classifier_confidence
 * @property array|null $parsed_data
 * @property string|null $parser_engine
 * @property string|null $parser_version
 * @property float|null $parser_confidence
 * @property array|null $resolved_data
 * @property string|null $resolver_engine
 * @property string|null $resolver_version
 * @property float|null $resolver_confidence
 * @property array|null $resolver_warnings
 * @property int|null $transaction_id
 * @property Carbon|null $completed_at
 * @property string|null $commit_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read TransactionLog|null $transaction
 * @property-read Collection<EvidenceProcessingLog> $processingLogs
 */
class Evidence extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'original_name',
        'stored_name',
        'mime_type',
        'extension',
        'size',
        'disk',
        'path',
        'status',
        'source',
        'processing_started_at',
        'processing_finished_at',
        'error_message',
        'retry_count',
        'last_processed_at',
        'ocr_text',
        'normalized_text',
        'normalization_duration_ms',
        'normalization_changes',
        'ocr_engine',
        'ocr_duration_ms',
        'ocr_version',
        'document_type',
        'classifier_engine',
        'classifier_version',
        'classifier_confidence',
        'parsed_data',
        'parser_engine',
        'parser_version',
        'parser_confidence',
        'resolved_data',
        'resolver_engine',
        'resolver_version',
        'resolver_confidence',
        'resolver_warnings',
        'transaction_id',
        'completed_at',
        'commit_version',
    ];

    protected $casts = [
        'size' => 'integer',
        'retry_count' => 'integer',
        'ocr_duration_ms' => 'integer',
        'normalization_duration_ms' => 'integer',
        'normalization_changes' => 'integer',
        'classifier_confidence' => 'float',
        'parser_confidence' => 'float',
        'resolver_confidence' => 'float',
        'parsed_data' => 'array',
        'resolved_data' => 'array',
        'resolver_warnings' => 'array',
        'status' => EvidenceStatus::class,
        'document_type' => DocumentType::class,
        'processing_started_at' => 'datetime',
        'processing_finished_at' => 'datetime',
        'last_processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ── Source constants ─────────────────────────────────────────────
    const SOURCE_CHAT_UPLOAD = 'CHAT_UPLOAD';

    const SOURCE_TELEGRAM = 'TELEGRAM';

    const SOURCE_API = 'API';

    const SOURCE_SHARE = 'SHARE_TARGET';

    // ── Relations ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TransactionLog::class);
    }

    public function processingLogs(): HasMany
    {
        return $this->hasMany(EvidenceProcessingLog::class);
    }

    // ── Status Helpers ────────────────────────────────────────────────

    public function isUploaded(): bool
    {
        return $this->status === EvidenceStatus::Uploaded;
    }

    public function isQueued(): bool
    {
        return $this->status === EvidenceStatus::Queued;
    }

    public function isProcessing(): bool
    {
        return $this->status === EvidenceStatus::Processing;
    }

    public function isReady(): bool
    {
        return $this->status === EvidenceStatus::Ready;
    }

    public function isCompleted(): bool
    {
        return $this->status === EvidenceStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === EvidenceStatus::Failed;
    }

    public function isResolved(): bool
    {
        return $this->status === EvidenceStatus::Resolved;
    }

    /**
     * Apakah evidence sedang dalam proses pipeline (bukan terminal state).
     */
    public function isInPipeline(): bool
    {
        return ! $this->status->isTerminal();
    }

    // ── Document Type Helpers ─────────────────────────────────────────

    public function isTransferReceipt(): bool
    {
        return $this->document_type === DocumentType::TransferReceipt;
    }

    public function isShoppingReceipt(): bool
    {
        return $this->document_type === DocumentType::ShoppingReceipt;
    }

    public function isQrisReceipt(): bool
    {
        return $this->document_type === DocumentType::QrisReceipt;
    }

    public function isBankStatement(): bool
    {
        return $this->document_type === DocumentType::BankStatement;
    }

    public function isPaymentReceipt(): bool
    {
        return $this->document_type === DocumentType::PaymentReceipt;
    }

    public function isTopupReceipt(): bool
    {
        return $this->document_type === DocumentType::TopupReceipt;
    }

    public function isWithdrawReceipt(): bool
    {
        return $this->document_type === DocumentType::WithdrawReceipt;
    }

    public function isDepositReceipt(): bool
    {
        return $this->document_type === DocumentType::DepositReceipt;
    }

    public function isInvoice(): bool
    {
        return $this->document_type === DocumentType::Invoice;
    }

    public function isUnknown(): bool
    {
        return $this->document_type === DocumentType::Unknown || $this->document_type === null;
    }

    // ── Data Accessor Helpers ─────────────────────────────────────────

    /**
     * Dapatkan parsed data sebagai EvidenceData DTO.
     */
    public function getParsedDataAttribute(): ?EvidenceData
    {
        $raw = $this->attributes['parsed_data'] ?? null;

        if ($raw === null) {
            return null;
        }

        $data = is_array($raw) ? $raw : json_decode($raw, true);

        if (! is_array($data)) {
            return null;
        }

        return EvidenceData::fromArray($data);
    }

    /**
     * Dapatkan resolved data sebagai TransactionDraft DTO.
     */
    public function getResolvedDataAttribute(): ?TransactionDraft
    {
        $raw = $this->attributes['resolved_data'] ?? null;

        if ($raw === null) {
            return null;
        }

        $data = is_array($raw) ? $raw : json_decode($raw, true);

        if (! is_array($data)) {
            return null;
        }

        return TransactionDraft::fromArray($data);
    }

    // ── Attributes ────────────────────────────────────────────────────

    /**
     * URL untuk mengakses file original.
     * Sekarang pakai route private chat.evidence.image agar foto tampil di chat
     * (storage evidence adalah private disk, tidak bisa diakses via /storage URL).
     */
    public function getUrlAttribute(): string
    {
        // Jika sedang dalam konteks web request, pakai route; fallback ke storage URL untuk CLI/queue
        try {
            if (app()->runningInConsole() === false) {
                return route('chat.evidence.image', ['uuid' => $this->uuid]);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Format ukuran file yang mudah dibaca (contoh: "2.4 MB").
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i > 0 ? 1 : 0).' '.$units[$i];
    }
}
