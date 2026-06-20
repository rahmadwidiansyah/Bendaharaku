<?php

declare(strict_types=1);

namespace App\Services\AI\Dataset;

use App\Models\AiTrainingSample;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DatasetExportService
{
    /**
     * Mengekspor data ke format JSONL yang kompatibel dengan OpenAI / LLaMA Fine-tuning.
     */
    public function exportToJsonl(): string
    {
        $samples = AiTrainingSample::where('is_verified', true)
            ->where('status', 'raw')
            ->get();

        if ($samples->isEmpty()) {
            throw new \Exception("Tidak ada data training baru yang tervalidasi.");
        }

        // Simpan di direktori internal storage/app/ai/datasets
        $filename = 'ai/datasets/finetune_' . now()->format('Ymd_His') . '.jsonl';
        $content = "";

        foreach ($samples as $sample) {
            $systemPrompt = "You are a financial AI. Extract transaction into JSON.";
            
            // Format percakapan standar industri AI (Chat Completion Format)
            $jsonlRow = [
                "messages" => [
                    ["role" => "system", "content" => $systemPrompt],
                    ["role" => "user", "content" => $sample->input_text],
                    ["role" => "assistant", "content" => json_encode($sample->expected_json)]
                ]
            ];
            
            $content .= json_encode($jsonlRow, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
            
            // Tandai agar tidak diekspor ganda di kemudian hari
            $sample->update(['status' => 'exported']);
        }

        Storage::disk('local')->put($filename, $content);

        return Storage::disk('local')->path($filename);
    }
}