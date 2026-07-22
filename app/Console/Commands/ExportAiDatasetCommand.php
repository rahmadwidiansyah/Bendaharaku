<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\Dataset\DatasetExportService;
use Illuminate\Support\Facades\Log;

class ExportAiDatasetCommand extends Command
{
    protected $signature = 'ai:export-dataset';
    protected $description = 'Mengekspor dataset dari koreksi transaksi ke format JSONL untuk Fine-Tuning AI.';

    public function handle(DatasetExportService $exportService): int
    {
        $this->info('Memulai ekstraksi dataset AI...');

        try {
            $absolutePath = $exportService->exportToJsonl();
            $this->info("Dataset berhasil diekspor ke: {$absolutePath}");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Gagal mengekspor dataset: ' . $e->getMessage());
            Log::error('AI Dataset Export Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
