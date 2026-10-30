<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SystemController — operasional queue untuk panel admin.
 *
 * Detail & aksi untuk kartu "Failed queue jobs" di Admin Dashboard:
 * - index()  : daftar failed jobs (terbaru dulu, payload diparse ringkas)
 * - retry()  : kembalikan satu job ke queue (queue:retry {id})
 * - retryAll : kembalikan seluruh failed jobs (queue:retry all)
 * - forget() : buang satu failed job (queue:forget {id})
 *
 * Semua aksi dicatat ke AdminAuditLog.
 */
class SystemController extends Controller
{
    public function failedJobs(): JsonResponse
    {
        if (! Schema::hasTable('failed_jobs')) {
            return response()->json(['jobs' => [], 'total' => 0]);
        }

        $jobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(100)
            ->get(['id', 'uuid', 'queue', 'payload', 'exception', 'failed_at'])
            ->map(fn ($job) => $this->formatJob($job))
            ->all();

        return response()->json([
            'jobs' => $jobs,
            'total' => count($jobs),
            'queue_pending' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
        ]);
    }

    public function retry(Request $request, int $id): JsonResponse
    {
        $job = $this->findOrFail($id);

        // Driver "database-uuids" mencari job via uuid, bukan id numerik.
        Artisan::call('queue:retry', ['id' => [$job->uuid]]);

        AdminAuditLog::log(
            $request->user(),
            'failed_job_retried',
            'FailedJob',
            $id,
            null,
            null,
            ['queue' => $job->queue, 'uuid' => $job->uuid, 'ip' => $request->ip()],
            true,
        );

        return response()->json(['ok' => true, 'message' => 'Job dikembalikan ke queue.']);
    }

    public function retryAll(Request $request): JsonResponse
    {
        $count = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        if ($count === 0) {
            return response()->json(['ok' => true, 'message' => 'Tidak ada failed job.']);
        }

        Artisan::call('queue:retry', ['id' => ['all']]);

        AdminAuditLog::log(
            $request->user(),
            'failed_jobs_retry_all',
            'FailedJob',
            null,
            null,
            null,
            ['count' => $count, 'ip' => $request->ip()],
            true,
        );

        return response()->json(['ok' => true, 'message' => "{$count} job dikembalikan ke queue."]);
    }

    public function forget(Request $request, int $id): JsonResponse
    {
        $job = $this->findOrFail($id);

        Artisan::call('queue:forget', ['id' => $job->uuid]);

        AdminAuditLog::log(
            $request->user(),
            'failed_job_deleted',
            'FailedJob',
            $id,
            null,
            null,
            ['queue' => $job->queue, 'uuid' => $job->uuid, 'ip' => $request->ip()],
            true,
        );

        return response()->json(['ok' => true, 'message' => 'Failed job dihapus.']);
    }

    private function findOrFail(int $id)
    {
        $job = DB::table('failed_jobs')->where('id', $id)->first();
        abort_if(! $job, 404, 'Failed job tidak ditemukan.');

        return $job;
    }

    /**
     * Payload failed_jobs berisi JSON job asli — ambil displayName agar
     * admin bisa membedakan job tanpa membaca stack trace mentah.
     */
    private function formatJob($job): array
    {
        $payload = json_decode($job->payload, true);
        $displayName = $payload['displayName'] ?? ($payload['job'] ?? 'Unknown job');

        return [
            'id' => $job->id,
            'uuid' => $job->uuid,
            'queue' => $job->queue,
            'job' => class_basename($displayName),
            'failed_at' => $job->failed_at,
            'exception' => mb_substr($job->exception ?? '', 0, 600),
        ];
    }
}
