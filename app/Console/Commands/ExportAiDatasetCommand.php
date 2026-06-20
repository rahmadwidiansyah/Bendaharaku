<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\Dataset\DatasetExportService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExportAiDatasetCommand extends Command
{
    protected $signature = 'ai:export-dataset {--train : Langsung kirim trigger training ke Python API}';
    protected $description = 'Mengekspor dataset dari koreksi transaksi ke format JSONL untuk Fine-Tuning AI.';

    public function handle(DatasetExportService $exportService): int
    {
        $this->info('Memulai ekstraksi dataset AI...');
        
        try {
            $absolutePath = $exportService->exportToJsonl();
            $this->info("✅ Dataset berhasil diekspor ke: {$absolutePath}");

            // Jika dipanggil dengan flag --train (php artisan ai:export-dataset --train)
            if ($this->option('train')) {
                $this->info('Mengirim sinyal retraining ke Python NLP Server...');
                $this->triggerPythonRetraining($absolutePath);
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Gagal mengekspor dataset: ' . $e->getMessage());
            Log::error('AI Dataset Export Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function triggerPythonRetraining(string $filePath): void
    {
        $url = config('services.python_ai.url', env('PYTHON_AI_URL'));
        $apiKey = config('services.python_ai.key', env('PYTHON_AI_KEY'));

        if (blank($url)) {
            $this->warn('URL Python AI belum diatur di .env. Lewati trigger training.');
            return;
        }

        try {
            // Asumsi server Python dan Laravel berada di satu mesin/docker network yang bisa berbagi volume storage
            $response = Http::withHeaders(['X-API-KEY' => $apiKey])
                ->timeout(10)
                ->post($url . '/train', [
                    'dataset_path' => $filePath
                ]);

            if ($response->successful()) {
                $this->info('✅ Python NLP Server menerima perintah retraining.');
            } else {
                $this->error('❌ Python NLP menolak request: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('❌ Gagal menghubungi Python Server: ' . $e->getMessage());
        }
    }
}