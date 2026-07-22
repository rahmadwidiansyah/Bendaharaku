<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Evidence;
use App\Services\Evidence\ProcessingLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvidenceDebugController extends Controller
{
    public function __construct(
        private readonly ProcessingLogService $processingLog,
    ) {}

    /**
     * Dapatkan timeline pemrosesan evidence.
     *
     * GET /api/chat/evidence/{uuid}/timeline
     */
    public function timeline(Request $request, string $uuid): JsonResponse
    {
        $evidence = Evidence::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $timeline = $this->processingLog->getTimeline($evidence);
        $totalDuration = $this->processingLog->getTotalPipelineDuration($evidence);

        return response()->json([
            'evidence_id' => $evidence->uuid,
            'current_status' => $evidence->status->value,
            'total_duration_ms' => $totalDuration,
            'timeline' => $timeline,
        ]);
    }

    /**
     * Dapatkan metrics pipeline evidence.
     *
     * GET /api/chat/evidence/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $hours = (int) $request->input('hours', 24);
        $metrics = $this->processingLog->getMetrics($hours);

        return response()->json($metrics);
    }

    /**
     * Health check untuk evidence pipeline.
     *
     * GET /api/chat/evidence/health
     */
    public function health(): JsonResponse
    {
        $health = $this->processingLog->getHealthStatus();

        $statusCode = match ($health['status']) {
            'healthy' => 200,
            'warning' => 200,
            'error' => 503,
            default => 200,
        };

        return response()->json($health, $statusCode);
    }
}
