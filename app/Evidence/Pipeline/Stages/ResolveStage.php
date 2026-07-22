<?php

declare(strict_types=1);

namespace App\Evidence\Pipeline\Stages;

use App\Evidence\Pipeline\Context\EvidenceContext;
use App\Evidence\Pipeline\Contracts\EvidenceStage;
use App\Evidence\Resolver\EvidenceResolver;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * ResolveStage — Resolve EvidenceData menjadi TransactionDraft.
 */
class ResolveStage implements EvidenceStage
{
    public function __construct(
        private readonly EvidenceResolver $resolver,
    ) {}

    public function handle(EvidenceContext $context, Closure $next): void
    {
        $start = microtime(true);

        Log::channel('evidence')->info('Resolve stage started', [
            'evidence_id' => $context->evidence->id,
        ]);

        if ($context->parsedData === null) {
            Log::channel('evidence')->warning('Resolve stage skipped (no parsed data)', [
                'evidence_id' => $context->evidence->id,
            ]);

            $duration = (int) ((microtime(true) - $start) * 1000);
            $context->recordStageDuration('RESOLVE', $duration);

            $next($context);

            return;
        }

        // Refresh evidence untuk data terbaru
        $context->evidence->refresh();

        $draft = $this->resolver->resolve($context->evidence, $context->parsedData);

        $context->draft = $draft;
        $context->warnings = array_merge($context->warnings, $draft->warnings);
        $context->metadata['resolver_confidence'] = $draft->confidence;
        $context->metadata['resolved'] = $draft->resolved;

        $duration = (int) ((microtime(true) - $start) * 1000);
        $context->recordStageDuration('RESOLVE', $duration);

        Log::channel('evidence')->info('Resolve stage completed', [
            'evidence_id' => $context->evidence->id,
            'transaction_type' => $draft->transactionType,
            'confidence' => $draft->confidence,
            'resolved' => $draft->resolved,
            'warnings' => $draft->warnings,
            'duration_ms' => $duration,
        ]);

        $next($context);
    }
}
