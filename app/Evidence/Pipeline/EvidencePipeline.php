<?php

declare(strict_types=1);

namespace App\Evidence\Pipeline;

use App\Evidence\Pipeline\Context\EvidenceContext;
use App\Evidence\Pipeline\Contracts\EvidenceStage;
use App\Services\Evidence\EvidencePipelineService;
use Closure;
use Illuminate\Support\Facades\Log;

class EvidencePipeline
{
    private array $stages = [];

    public function through(array $stages): self
    {
        $this->stages = $stages;

        return $this;
    }

    public function process(EvidenceContext $context): EvidenceContext
    {
        $pipelineStart = microtime(true);

        $stageNames = array_map(
            fn ($s) => is_string($s) ? class_basename($s) : class_basename(get_class($s)),
            $this->stages
        );

        Log::channel('evidence')->info('Pipeline started', [
            'evidence_id' => $context->evidence->id,
            'stages' => $stageNames,
        ]);

        $pipeline = $this->buildPipeline($this->stages, 0);
        $pipeline($context);

        $totalDuration = (int) ((microtime(true) - $pipelineStart) * 1000);
        $context->recordStageDuration('PIPELINE', $totalDuration);

        Log::channel('evidence')->info('Pipeline completed', [
            'evidence_id' => $context->evidence->id,
            'total_duration_ms' => $totalDuration,
            'stage_durations' => $context->stageDurations,
            'warnings' => $context->warnings,
        ]);

        return $context;
    }

    private function buildPipeline(array $stages, int $index): Closure
    {
        if ($index >= count($stages)) {
            return function (EvidenceContext $ctx) {
                // End of pipeline — no-op
            };
        }

        $stage = $this->resolveStage($stages[$index]);
        $next = $this->buildPipeline($stages, $index + 1);

        return function (EvidenceContext $ctx) use ($stage, $next, $stages, $index) {
            $stageName = class_basename($stage);
            $start = microtime(true);

            Log::channel('evidence')->info("{$stageName} Started", [
                'evidence_id' => $ctx->evidence->id,
                'stage_index' => $index,
            });

            try {
                $stage->handle($ctx, $next);

                $duration = (int) ((microtime(true) - $start) * 1000);
                $stageKey = strtoupper(str_replace('Stage', '', $stageName));
                $ctx->recordStageDuration($stageKey, $duration);

                Log::channel('evidence')->info("{$stageName} Finished", [
                    'evidence_id' => $ctx->evidence->id,
                    'duration_ms' => $duration,
                ]);

            } catch (\Throwable $e) {
                $duration = (int) ((microtime(true) - $start) * 1000);
                $stageKey = strtoupper(str_replace('Stage', '', $stageName));
                $ctx->recordStageDuration($stageKey, $duration);

                Log::channel('evidence')->error("{$stageName} Failed", [
                    'evidence_id' => $ctx->evidence->id,
                    'duration_ms' => $duration,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                $pipelineService = app(EvidencePipelineService::class);
                $pipelineService->fail(
                    evidence: $ctx->evidence,
                    errorMessage: "[{$stageName}] {$e->getMessage()}",
                    stage: $stageKey,
                    exception: $e,
                );

                throw $e;
            }
        };
    }

    private function resolveStage(string|EvidenceStage $stage): EvidenceStage
    {
        if ($stage instanceof EvidenceStage) {
            return $stage;
        }

        return app($stage);
    }
}
