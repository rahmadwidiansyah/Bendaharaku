<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EvidenceStatus;
use App\Models\Evidence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupStaleEvidenceCommand extends Command
{
    protected $signature = 'evidence:cleanup
                            {--hours=24 : Hapus evidence yang gagal lebih dari N jam}
                            {--dry-run : Tampilkan evidence yang akan dihapus tanpa menghapus}';

    protected $description = 'Hapus evidence yang gagal atau stuck terlalu lama';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $this->info("Mencari evidence yang gagal > {$hours} jam...");

        $staleEvidence = Evidence::whereIn('status', [EvidenceStatus::Failed, EvidenceStatus::Processing])
            ->where('created_at', '<', now()->subHours($hours))
            ->get();

        if ($staleEvidence->isEmpty()) {
            $this->info('Tidak ada evidence yang perlu dihapus.');

            return self::SUCCESS;
        }

        $this->info("Ditemukan {$staleEvidence->count()} evidence yang perlu dihapus.");

        if ($dryRun) {
            $table = $staleEvidence->map(fn ($e) => [
                'uuid' => $e->uuid,
                'status' => $e->status->value,
                'created_at' => $e->created_at->toDateTimeString(),
                'retry_count' => $e->retry_count,
            ])->toArray();

            $this->table(['UUID', 'Status', 'Created At', 'Retry Count'], $table);

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($staleEvidence->count());
        $bar->start();

        foreach ($staleEvidence as $evidence) {
            // Hapus file terkait
            if ($evidence->file_path && file_exists(storage_path('app/private/evidence/'.$evidence->file_path))) {
                unlink(storage_path('app/private/evidence/'.$evidence->file_path));
            }

            // Hapus processing logs
            DB::table('evidence_processing_logs')
                ->where('evidence_id', $evidence->id)
                ->delete();

            // Hapus evidence record
            $evidence->delete();

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Berhasil menghapus {$staleEvidence->count()} evidence.");

        return self::SUCCESS;
    }
}
