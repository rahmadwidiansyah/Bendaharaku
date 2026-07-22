<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Evidence;
use App\Services\Evidence\ProcessingLogService;
use Illuminate\Console\Command;

class EvidenceDebugCommand extends Command
{
    protected $signature = 'evidence:debug {id : Evidence ID or UUID}';

    protected $description = 'Debug evidence pipeline status, stage history, and durations';

    public function handle(ProcessingLogService $processingLog): int
    {
        $id = $this->argument('id');

        $evidence = is_numeric($id)
            ? Evidence::find((int) $id)
            : Evidence::where('uuid', $id)->first();

        if (! $evidence) {
            $this->error("Evidence not found: {$id}");
            return self::FAILURE;
        }

        $this->newLine();
        $this->line(sprintf(' <fg=cyan>══════════════════════ Evidence Debug ══════════════════════</>'));
        $this->newLine();

        // ── Basic Info ──────────────────────────────────────────────
        $this->line(' <fg=yellow>Basic Information</>');
        $this->line(' ────────────────────────────────────────────────────');
        $this->info(" ID:                     {$evidence->id}");
        $this->info(" UUID:                   {$evidence->uuid}");
        $this->info(" Status:                 {$evidence->status->value}");
        $this->info(" Source:                 {$evidence->source}");
        $this->info(" Original Name:          {$evidence->original_name}");
        $this->info(" Size:                   {$evidence->formatted_size}");
        $this->info(" MIME Type:              {$evidence->mime_type}");
        $this->info(" Storage Path:           {$evidence->disk}:{$evidence->path}");
        $this->info(" Retry Count:            {$evidence->retry_count}");
        $this->info(" Document Type:          " . ($evidence->document_type?->value ?? 'N/A'));
        $this->info(" Classifier Confidence:  " . ($evidence->classifier_confidence !== null ? round($evidence->classifier_confidence, 4) : 'N/A'));
        $this->info(" Parser Confidence:      " . ($evidence->parser_confidence !== null ? round($evidence->parser_confidence, 4) : 'N/A'));
        $this->info(" Resolver Confidence:    " . ($evidence->resolver_confidence !== null ? round($evidence->resolver_confidence, 4) : 'N/A'));

        $this->newLine();

        // ── OCR Info ────────────────────────────────────────────────
        $this->line(' <fg=yellow>OCR Information</>');
        $this->line(' ────────────────────────────────────────────────────');
        $this->info(" OCR Text Length:        " . (strlen($evidence->ocr_text ?? '') ?: 'N/A'));
        $this->info(" OCR Engine:             " . ($evidence->ocr_engine ?? 'N/A'));
        $this->info(" OCR Duration (ms):      " . ($evidence->ocr_duration_ms ?? 'N/A'));
        $this->info(" Normalized Text Length: " . (strlen($evidence->normalized_text ?? '') ?: 'N/A'));
        $this->info(" Normalization Duration: " . ($evidence->normalization_duration_ms ?? 'N/A'));
        $this->info(" Normalization Changes:  " . ($evidence->normalization_changes ?? 'N/A'));

        $this->newLine();

        // ── Timing ──────────────────────────────────────────────────
        $this->line(' <fg=yellow>Timing</>');
        $this->line(' ────────────────────────────────────────────────────');
        $this->info(" Created At:             " . ($evidence->created_at?->toIso8601String() ?? 'N/A'));
        $this->info(" Processing Started:     " . ($evidence->processing_started_at?->toIso8601String() ?? 'N/A'));
        $this->info(" Processing Finished:    " . ($evidence->processing_finished_at?->toIso8601String() ?? 'N/A'));
        $this->info(" Last Processed:         " . ($evidence->last_processed_at?->toIso8601String() ?? 'N/A'));

        $this->newLine();

        // ── Error ───────────────────────────────────────────────────
        if ($evidence->error_message) {
            $this->line(' <fg=red>Error</>');
            $this->line(' ────────────────────────────────────────────────────');
            $this->error(" Error Message:          {$evidence->error_message}");
            $this->newLine();
        }

        // ── Stage Timeline ──────────────────────────────────────────
        $timeline = $processingLog->getTimeline($evidence);
        $this->line(' <fg=yellow>Stage Timeline</>');
        $this->line(' ────────────────────────────────────────────────────');

        if (empty($timeline)) {
            $this->warn(' No stage history found.');
        } else {
            foreach ($timeline as $entry) {
                $stage = str_pad($entry['stage'], 12);
                $status = str_pad($entry['status'], 16);
                $duration = $entry['duration_ms'] !== null
                    ? str_pad("{$entry['duration_ms']}ms", 10)
                    : str_pad('N/A', 10);
                $time = $entry['created_at'] ?? '';
                $msg = $entry['message'] ?? '';

                $line = " {$stage} {$status} {$duration} {$time}";
                if ($entry['status'] === 'FAILED') {
                    $this->error($line);
                    if ($msg) {
                        $this->error("   └─ {$msg}");
                    }
                } else {
                    $this->line($line);
                }
            }
        }

        $this->newLine();

        // ── Total Pipeline Duration ─────────────────────────────────
        $totalDuration = $processingLog->getTotalPipelineDuration($evidence);
        $this->line(' <fg=yellow>Total Pipeline Duration</>');
        $this->line(' ────────────────────────────────────────────────────');
        $this->info(' ' . ($totalDuration !== null ? "{$totalDuration} ms" : 'N/A'));

        $this->newLine();
        $this->line(' <fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
