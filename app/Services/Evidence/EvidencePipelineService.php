<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Enums\DocumentType;
use App\Enums\EvidenceStatus;
use App\Evidence\DTO\EvidenceData;
use App\Evidence\DTO\TransactionDraft;
use App\Evidence\Events\EvidenceProcessingCompleted;
use App\Evidence\Events\EvidenceProcessingStarted;
use App\Evidence\Events\EvidenceQueued;
use App\Models\Evidence;
use Illuminate\Support\Facades\Log;

/**
 * EvidencePipelineService — Orchestrator untuk pipeline pemrosesan evidence.
 *
 * Lifecycle:
 *   queue()           → UPLOADED → QUEUED
 *   startProcessing() → QUEUED → PROCESSING
 *   ocrCompleted()    → PROCESSING → OCR_COMPLETED
 *   classified()      → OCR_COMPLETED → CLASSIFIED
 *   parsed()          → CLASSIFIED → PARSED
 *   resolved()        → PARSED → RESOLVED
 *   complete()        → RESOLVED → READY
 *   fail()            → any → FAILED
 *   retry()           → FAILED → QUEUED
 */
class EvidencePipelineService
{
    public function __construct(
        private readonly ProcessingLogService $processingLog,
    ) {}

    /**
     * Tandai evidence siap diproses (QUEUED).
     */
    public function queue(Evidence $evidence): Evidence
    {
        $statusBefore = $evidence->status->value;
        $evidence->update(['status' => EvidenceStatus::Queued]);

        $this->processingLog->log(
            evidence: $evidence,
            stage: 'QUEUE',
            statusBefore: $statusBefore,
            statusAfter: 'QUEUED',
        );

        Log::info('Evidence queued', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
        ]);

        event(new EvidenceQueued($evidence));

        return $evidence->fresh();
    }

    /**
     * Mulai proses evidence (PROCESSING).
     */
    public function startProcessing(Evidence $evidence): Evidence
    {
        $statusBefore = $evidence->status->value;
        $evidence->update([
            'status' => EvidenceStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $this->processingLog->log(
            evidence: $evidence,
            stage: 'PROCESS',
            statusBefore: $statusBefore,
            statusAfter: 'PROCESSING',
        );

        Log::info('Evidence processing started', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
        ]);

        event(new EvidenceProcessingStarted($evidence));

        return $evidence->fresh();
    }

    /**
     * Tandai OCR selesai (OCR_COMPLETED).
     */
    public function ocrCompleted(Evidence $evidence, string $ocrText, string $engine, int $durationMs): Evidence
    {
        $statusBefore = $evidence->status->value;
        $evidence->update([
            'status' => EvidenceStatus::OcrCompleted,
            'ocr_text' => $ocrText,
            'ocr_engine' => $engine,
            'ocr_duration_ms' => $durationMs,
            'ocr_version' => config('evidence.pipeline_version', '1.0'),
            'last_processed_at' => now(),
        ]);

        $this->processingLog->log(
            evidence: $evidence,
            stage: 'OCR',
            statusBefore: $statusBefore,
            statusAfter: 'OCR_COMPLETED',
            durationMs: $durationMs,
            metadata: [
                'engine' => $engine,
                'text_length' => strlen($ocrText),
            ],
        );

        Log::info('Evidence OCR completed', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'engine' => $engine,
            'duration_ms' => $durationMs,
            'text_length' => strlen($ocrText),
        ]);

        return $evidence->fresh();
    }

    /**
     * Tandai klasifikasi selesai (CLASSIFIED).
     */
    public function classified(
        Evidence $evidence,
        DocumentType $documentType,
        float $confidence,
        string $engine,
    ): Evidence {
        $statusBefore = $evidence->status->value;
        $evidence->update([
            'status' => EvidenceStatus::Classified,
            'document_type' => $documentType,
            'classifier_confidence' => $confidence,
            'classifier_engine' => $engine,
            'classifier_version' => config('evidence.pipeline_version', '1.0'),
            'last_processed_at' => now(),
        ]);

        $this->processingLog->log(
            evidence: $evidence,
            stage: 'CLASSIFY',
            statusBefore: $statusBefore,
            statusAfter: 'CLASSIFIED',
            metadata: [
                'document_type' => $documentType->value,
                'confidence' => $confidence,
                'engine' => $engine,
            ],
        );

        Log::info('Evidence classified', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'document_type' => $documentType->value,
            'confidence' => $confidence,
            'engine' => $engine,
        ]);

        return $evidence->fresh();
    }

    /**
     * Tandai parsing selesai (PARSED).
     */
    public function parsed(Evidence $evidence, EvidenceData $data, string $engine): Evidence
    {
        $statusBefore = $evidence->status->value;
        $evidence->update([
            'status' => EvidenceStatus::Parsed,
            'parsed_data' => $data->toArray(),
            'parser_engine' => $engine,
            'parser_version' => config('evidence.pipeline_version', '1.0'),
            'parser_confidence' => $data->confidence,
            'last_processed_at' => now(),
        ]);

        $this->processingLog->log(
            evidence: $evidence,
            stage: 'PARSE',
            statusBefore: $statusBefore,
            statusAfter: 'PARSED',
            metadata: [
                'document_type' => $data->documentType->value,
                'confidence' => $data->confidence,
                'engine' => $engine,
                'amount' => $data->amount,
            ],
        );

        Log::info('Evidence parsed', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'document_type' => $data->documentType->value,
            'confidence' => $data->confidence,
            'engine' => $engine,
            'amount' => $data->amount,
            'reference' => $data->referenceNumber,
        ]);

        return $evidence->fresh();
    }

    /**
     * Tandai resolver selesai (RESOLVED).
     */
    public function resolved(Evidence $evidence, TransactionDraft $draft, string $engine): Evidence
    {
        $statusBefore = $evidence->status->value;
        $evidence->update([
            'status' => EvidenceStatus::Resolved,
            'resolved_data' => $draft->toArray(),
            'resolver_engine' => $engine,
            'resolver_version' => config('evidence.pipeline_version', '1.0'),
            'resolver_confidence' => $draft->confidence,
            'resolver_warnings' => $draft->warnings,
            'last_processed_at' => now(),
        ]);

        $this->processingLog->log(
            evidence: $evidence,
            stage: 'RESOLVE',
            statusBefore: $statusBefore,
            statusAfter: 'RESOLVED',
            metadata: [
                'transaction_type' => $draft->transactionType,
                'confidence' => $draft->confidence,
                'resolved' => $draft->resolved,
                'warnings' => $draft->warnings,
            ],
        );

        Log::info('Evidence resolved', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'transaction_type' => $draft->transactionType,
            'confidence' => $draft->confidence,
            'resolved' => $draft->resolved,
            'warnings' => $draft->warnings,
        ]);

        return $evidence->fresh();
    }

    /**
     * Tandai evidence selesai diproses (READY).
     */
    public function complete(Evidence $evidence): Evidence
    {
        $statusBefore = $evidence->status->value;
        $evidence->update([
            'status' => EvidenceStatus::Ready,
            'processing_finished_at' => now(),
            'last_processed_at' => now(),
            'error_message' => null,
        ]);

        $this->processingLog->log(
            evidence: $evidence,
            stage: 'COMPLETE',
            statusBefore: $statusBefore,
            statusAfter: 'READY',
        );

        Log::info('Evidence processing completed', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
        ]);

        event(new EvidenceProcessingCompleted($evidence));

        return $evidence->fresh();
    }

    /**
     * Tandai evidence gagal diproses (FAILED).
     */
    public function fail(Evidence $evidence, string $errorMessage, ?string $stage = null, ?\Throwable $exception = null): Evidence
    {
        $statusBefore = $evidence->status->value;
        $evidence->update([
            'status' => EvidenceStatus::Failed,
            'processing_finished_at' => now(),
            'last_processed_at' => now(),
            'error_message' => $errorMessage,
        ]);

        $this->processingLog->log(
            evidence: $evidence,
            stage: $stage ?? 'UNKNOWN',
            statusBefore: $statusBefore,
            statusAfter: 'FAILED',
            message: $errorMessage,
            metadata: [
                'error_class' => $exception ? get_class($exception) : 'Unknown',
                'retry_count' => $evidence->retry_count,
            ],
        );

        Log::channel(config('evidence.log_channel', 'evidence'))->error('Evidence processing failed', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'stage' => $stage,
            'error' => $errorMessage,
            'retry_count' => $evidence->retry_count,
        ]);

        return $evidence->fresh();
    }

    /**
     * Coba ulang evidence yang gagal (FAILED → QUEUED).
     */
    public function retry(Evidence $evidence): Evidence
    {
        $statusBefore = $evidence->status->value;
        $evidence->update([
            'status' => EvidenceStatus::Queued,
            'error_message' => null,
            'retry_count' => $evidence->retry_count + 1,
        ]);

        $this->processingLog->log(
            evidence: $evidence,
            stage: 'RETRY',
            statusBefore: $statusBefore,
            statusAfter: 'QUEUED',
            metadata: [
                'retry_count' => $evidence->retry_count,
            ],
        );

        Log::info('Evidence retry queued', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'retry_count' => $evidence->retry_count,
        ]);

        event(new EvidenceQueued($evidence));

        return $evidence->fresh();
    }

    /**
     * Dapatkan stage terakhir yang berhasil (untuk pipeline resume).
     */
    public function getLastSuccessfulStage(Evidence $evidence): ?string
    {
        return $this->processingLog->getLastSuccessfulStage($evidence);
    }
}
