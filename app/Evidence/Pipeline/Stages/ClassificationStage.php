<?php

declare(strict_types=1);

namespace App\Evidence\Pipeline\Stages;

use App\Evidence\Pipeline\Context\EvidenceContext;
use App\Evidence\Pipeline\Contracts\EvidenceStage;
use App\Services\Evidence\DocumentClassifier;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * ClassificationStage — Klasifikasi jenis dokumen dari teks.
 */
class ClassificationStage implements EvidenceStage
{
    public function __construct(
        private readonly DocumentClassifier $classifier,
    ) {}

    public function handle(EvidenceContext $context, Closure $next): void
    {
        $start = microtime(true);

        Log::channel('evidence')->info('Classification stage started', [
            'evidence_id' => $context->evidence->id,
        ]);

        // Refresh evidence untuk mendapatkan normalized_text terbaru
        $context->evidence->refresh();

        $classification = $this->classifier->classify($context->evidence);

        $context->documentType = $classification['document_type'];
        $context->metadata['classifier_confidence'] = $classification['confidence'];
        $context->metadata['matched_keywords'] = $classification['matched_keywords'];

        $duration = (int) ((microtime(true) - $start) * 1000);
        $context->recordStageDuration('CLASSIFY', $duration);

        Log::channel('evidence')->info('Classification stage completed', [
            'evidence_id' => $context->evidence->id,
            'document_type' => $classification['document_type']->value,
            'confidence' => $classification['confidence'],
            'duration_ms' => $duration,
        ]);

        $next($context);
    }
}
