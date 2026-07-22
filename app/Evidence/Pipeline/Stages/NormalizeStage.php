<?php

declare(strict_types=1);

namespace App\Evidence\Pipeline\Stages;

use App\Evidence\Pipeline\Context\EvidenceContext;
use App\Evidence\Pipeline\Contracts\EvidenceStage;
use App\Services\Evidence\OCRTextNormalizer;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * NormalizeStage — Normalisasi teks OCR sebelum classifier dan parser.
 */
class NormalizeStage implements EvidenceStage
{
    public function __construct(
        private readonly OCRTextNormalizer $normalizer,
    ) {}

    public function handle(EvidenceContext $context, Closure $next): void
    {
        $start = microtime(true);

        Log::channel('evidence')->info('Normalize stage started', [
            'evidence_id' => $context->evidence->id,
            'input_length' => strlen($context->ocrText ?? ''),
        ]);

        $rawText = $context->ocrText ?? '';

        if (trim($rawText) === '') {
            $context->normalizedText = '';
            $context->normalizationChanges = 0;

            $duration = (int) ((microtime(true) - $start) * 1000);
            $context->recordStageDuration('NORMALIZE', $duration);

            Log::channel('evidence')->info('Normalize stage skipped (empty text)', [
                'evidence_id' => $context->evidence->id,
                'duration_ms' => $duration,
            ]);

            $next($context);

            return;
        }

        $result = $this->normalizer->normalize($rawText);

        $context->normalizedText = $result['normalized'];
        $context->normalizationChanges = $result['changes'];
        $context->metadata['normalization_changes'] = $result['changes'];

        // Simpan normalized_text ke database
        $context->evidence->update([
            'normalized_text' => $result['normalized'],
            'normalization_duration_ms' => $result['duration_ms'],
            'normalization_changes' => $result['changes'],
        ]);

        $duration = (int) ((microtime(true) - $start) * 1000);
        $context->recordStageDuration('NORMALIZE', $duration);

        Log::channel('evidence')->info('Normalize stage completed', [
            'evidence_id' => $context->evidence->id,
            'changes' => $result['changes'],
            'original_length' => strlen($rawText),
            'normalized_length' => strlen($result['normalized']),
            'duration_ms' => $duration,
        ]);

        $next($context);
    }
}
