<?php

declare(strict_types=1);

namespace App\Evidence\Jobs;

use App\Enums\DocumentType;
use App\Evidence\Pipeline\Context\EvidenceContext;
use App\Evidence\Pipeline\EvidencePipeline;
use App\Evidence\Pipeline\Stages\ClassificationStage;
use App\Evidence\Pipeline\Stages\NormalizeStage;
use App\Evidence\Pipeline\Stages\OCRStage;
use App\Evidence\Pipeline\Stages\ParsingStage;
use App\Evidence\Pipeline\Stages\ResolveStage;
use App\Models\Evidence;
use App\Services\Evidence\EvidencePipelineService;
use App\Services\Evidence\ProcessingLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEvidenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $backoff = 30;

    private const STAGES = [
        OCRStage::class,
        NormalizeStage::class,
        ClassificationStage::class,
        ParsingStage::class,
        ResolveStage::class,
    ];

    public function __construct(
        public int $evidenceId,
    ) {}

    public function handle(
        EvidencePipeline $pipeline,
        EvidencePipelineService $pipelineService,
        ProcessingLogService $processingLog,
    ): void {
        $evidence = Evidence::find($this->evidenceId);

        if (! $evidence) {
            Log::channel('evidence')->warning('ProcessEvidenceJob: evidence not found', [
                'evidence_id' => $this->evidenceId,
            ]);

            return;
        }

        $start = microtime(true);

        $context = new EvidenceContext($evidence);

        Log::channel('evidence')->info('ProcessEvidenceJob: starting pipeline', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'retry_count' => $evidence->retry_count,
            'stages' => array_map(fn ($s) => class_basename($s), self::STAGES),
        ]);

        $pipelineService->startProcessing($evidence);

        $pipeline->through(self::STAGES)->process($context);

        $this->persistResults($evidence, $context, $pipelineService);

        $totalMs = (int) ((microtime(true) - $start) * 1000);

        Log::channel('evidence')->info('ProcessEvidenceJob: pipeline completed', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'total_duration_ms' => $totalMs,
            'stages_completed' => array_keys($context->stageDurations),
            'stage_durations' => $context->stageDurations,
            'warnings' => $context->warnings,
        ]);
    }

    private function persistResults(
        Evidence $evidence,
        EvidenceContext $context,
        EvidencePipelineService $pipelineService,
    ): void {
        if ($context->ocrText !== null) {
            $pipelineService->ocrCompleted(
                evidence: $evidence,
                ocrText: $context->ocrText,
                engine: $context->metadata['ocr_engine'] ?? 'unknown',
                durationMs: $context->stageDurations['OCR'] ?? 0,
            );
        }

        if ($context->documentType !== null) {
            $pipelineService->classified(
                evidence: $evidence,
                documentType: $context->documentType,
                confidence: $context->metadata['classifier_confidence'] ?? 0.0,
                engine: config('classifier.engine', 'RuleBased'),
            );
        }

        if ($context->parsedData !== null) {
            $engine = $context->metadata['parser_engine'] ?? match ($context->documentType) {
                DocumentType::QrisReceipt => 'QrisReceiptParser',
                DocumentType::ShoppingReceipt => 'ShoppingReceiptParser',
                default => 'TransferReceiptParser',
            };

            $pipelineService->parsed(
                evidence: $evidence,
                data: $context->parsedData,
                engine: $engine,
            );
        }

        if ($context->draft !== null) {
            $pipelineService->resolved(
                evidence: $evidence,
                draft: $context->draft,
                engine: 'EvidenceResolver',
            );
        }

        $pipelineService->complete($evidence);
    }

    public function failed(\Throwable $exception): void
    {
        $evidence = Evidence::find($this->evidenceId);

        if (! $evidence) {
            Log::channel('evidence')->error('ProcessEvidenceJob: permanently failed — evidence not found', [
                'evidence_id' => $this->evidenceId,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $pipeline = app(EvidencePipelineService::class);
        $pipeline->fail($evidence, $exception->getMessage(), exception: $exception);

        $previous = $exception->getPrevious();
        $previousMsg = $previous ? $previous->getMessage() : null;
        $previousFile = $previous ? $previous->getFile().':'.$previous->getLine() : null;

        Log::channel('evidence')->error(sprintf(
            "Evidence Processing Failed\n\nEvidence:\n- ID: %d\n- UUID: %s\n\nStage:\n%s\n\nMessage:\n%s\n\nFile:\n%s:%d\n\nDuration:\n%d ms\n\nPrevious Exception:\n- %s\n- %s\n\nStacktrace:\n%s",
            $evidence->id,
            $evidence->uuid,
            $evidence->status->value,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $evidence->processing_started_at
                ? (int) (now()->diffInMilliseconds($evidence->processing_started_at))
                : 0,
            $previousMsg ?? 'N/A',
            $previousFile ?? 'N/A',
            $exception->getTraceAsString(),
        ));
    }
}
