<?php

declare(strict_types=1);

namespace App\Evidence\Pipeline\Stages;

use App\Evidence\Pipeline\Context\EvidenceContext;
use App\Evidence\Pipeline\Contracts\EvidenceStage;
use App\Services\Evidence\OCRClient;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * OCRStage — Ekstrak teks dari gambar menggunakan OCR service.
 */
class OCRStage implements EvidenceStage
{
    public function __construct(
        private readonly OCRClient $ocrClient,
    ) {}

    public function handle(EvidenceContext $context, Closure $next): void
    {
        $start = microtime(true);

        Log::channel('evidence')->info('OCR stage started', [
            'evidence_id' => $context->evidence->id,
        ]);

        $result = $this->ocrClient->extract($context->evidence);

        $context->ocrText = $result['text'];
        $context->metadata['ocr_engine'] = $result['engine'];
        $context->metadata['ocr_text_length'] = strlen($result['text']);

        $duration = (int) ((microtime(true) - $start) * 1000);
        $context->recordStageDuration('OCR', $duration);

        Log::channel('evidence')->info('OCR stage completed', [
            'evidence_id' => $context->evidence->id,
            'engine' => $result['engine'],
            'duration_ms' => $duration,
            'text_length' => strlen($result['text']),
        ]);

        $next($context);
    }
}
