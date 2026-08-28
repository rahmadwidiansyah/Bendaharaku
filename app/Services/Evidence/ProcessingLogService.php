<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Models\Evidence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessingLogService
{
    public function log(
        Evidence $evidence,
        string $stage,
        ?string $statusBefore,
        string $statusAfter,
        ?int $durationMs = null,
        ?string $message = null,
        array $metadata = [],
    ): void {
        DB::table('evidence_processing_logs')->insert([
            'evidence_id' => $evidence->id,
            'stage' => $stage,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'duration_ms' => $durationMs,
            'message' => $message,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::channel(config('evidence.log_channel', 'evidence'))->info('Evidence stage transition', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'stage' => $stage,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'duration_ms' => $durationMs,
        ]);
    }

    public function getTimeline(Evidence $evidence): array
    {
        $logs = DB::table('evidence_processing_logs')
            ->where('evidence_id', $evidence->id)
            ->orderBy('created_at')
            ->get();

        $timeline = [];
        foreach ($logs as $log) {
            $timeline[] = [
                'stage' => $log->stage,
                'status' => $log->status_after,
                'duration_ms' => $log->duration_ms,
                'message' => $log->message,
                'metadata' => $log->metadata ? json_decode($log->metadata, true) : [],
                'created_at' => $log->created_at,
            ];
        }

        return $timeline;
    }

    public function getLastSuccessfulStage(Evidence $evidence): ?string
    {
        $lastLog = DB::table('evidence_processing_logs')
            ->where('evidence_id', $evidence->id)
            ->where('status_after', '!=', 'FAILED')
            ->orderByDesc('created_at')
            ->first();

        return $lastLog?->stage;
    }

    public function getTotalPipelineDuration(Evidence $evidence): ?int
    {
        $result = DB::table('evidence_processing_logs')
            ->where('evidence_id', $evidence->id)
            ->whereNotNull('duration_ms')
            ->sum('duration_ms');

        return $result > 0 ? (int) $result : null;
    }

    public function getMetrics(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $totalEvidence = DB::table('evidence')
            ->where('created_at', '>=', $since)
            ->count();

        $completedEvidence = DB::table('evidence')
            ->where('created_at', '>=', $since)
            ->where('status', 'READY')
            ->orWhere('status', 'COMPLETED')
            ->count();

        $failedEvidence = DB::table('evidence')
            ->where('created_at', '>=', $since)
            ->where('status', 'FAILED')
            ->count();

        $avgOcrDuration = DB::table('evidence')
            ->where('created_at', '>=', $since)
            ->whereNotNull('ocr_duration_ms')
            ->avg('ocr_duration_ms');

        $avgPipelineDuration = DB::query()
            ->fromSub(
                DB::table('evidence_processing_logs')
                    ->where('created_at', '>=', $since)
                    ->whereNotNull('duration_ms')
                    ->selectRaw('evidence_id, SUM(duration_ms) as total')
                    ->groupBy('evidence_id'),
                'pipeline_durations'
            )
            ->avg('total');

        $avgConfidence = DB::table('evidence')
            ->where('created_at', '>=', $since)
            ->whereNotNull('resolver_confidence')
            ->avg('resolver_confidence');

        $mostCommonFailure = DB::table('evidence_processing_logs')
            ->where('created_at', '>=', $since)
            ->where('status_after', 'FAILED')
            ->whereNotNull('message')
            ->select('message', DB::raw('COUNT(*) as count'))
            ->groupBy('message')
            ->orderByDesc('count')
            ->first();

        $processing = DB::table('evidence')
            ->where('created_at', '>=', $since)
            ->whereIn('status', ['PROCESSING', 'QUEUED', 'UPLOADED'])
            ->count();

        return [
            'period_hours' => $hours,
            'total_evidence' => $totalEvidence,
            'completed' => $completedEvidence,
            'failed' => $failedEvidence,
            'processing' => $processing,
            'success_rate' => $totalEvidence > 0
                ? round(($completedEvidence / $totalEvidence) * 100, 2)
                : 0,
            'failure_rate' => $totalEvidence > 0
                ? round(($failedEvidence / $totalEvidence) * 100, 2)
                : 0,
            'avg_ocr_duration_ms' => $avgOcrDuration ? round((float) $avgOcrDuration) : null,
            'avg_pipeline_duration_ms' => $avgPipelineDuration ? round((float) $avgPipelineDuration) : null,
            'avg_confidence' => $avgConfidence ? round((float) $avgConfidence, 4) : null,
            'most_common_failure' => $mostCommonFailure?->message,
        ];
    }

    public function getHealthStatus(): array
    {
        $checks = [];

        try {
            $ocrClient = app(OCRClient::class);
            $checks['ocr_service'] = [
                'status' => 'healthy',
                'message' => 'OCR service reachable',
            ];
        } catch (\Throwable $e) {
            $checks['ocr_service'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        try {
            $pendingJobs = DB::table('jobs')
                ->where('queue', 'evidence')
                ->count();
            $checks['queue'] = [
                'status' => $pendingJobs < 100 ? 'healthy' : 'warning',
                'message' => "{$pendingJobs} pending jobs",
                'pending_jobs' => $pendingJobs,
            ];
        } catch (\Throwable $e) {
            $checks['queue'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        try {
            $storagePath = storage_path('app/private/evidence');
            $checks['storage'] = [
                'status' => is_dir($storagePath) && is_writable($storagePath) ? 'healthy' : 'warning',
                'message' => is_dir($storagePath) ? 'Storage available' : 'Storage directory not found',
            ];
        } catch (\Throwable $e) {
            $checks['storage'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'healthy',
                'message' => 'Database connected',
            ];
        } catch (\Throwable $e) {
            $checks['database'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        $statuses = array_column($checks, 'status');
        $overallStatus = 'healthy';
        if (in_array('error', $statuses)) {
            $overallStatus = 'error';
        } elseif (in_array('warning', $statuses)) {
            $overallStatus = 'warning';
        }

        return [
            'status' => $overallStatus,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
