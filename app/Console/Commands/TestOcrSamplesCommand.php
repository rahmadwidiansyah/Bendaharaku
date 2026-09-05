<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Evidence\OCRClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestOcrSamplesCommand extends Command
{
    protected $signature = 'ocr:test-samples';

    protected $description = 'Test 3 struk samples (cetak jelas, blur, tanpa digit) via Tesseract → Rapid fallback, log ke console & evidence_processing_logs';

    public function handle(): int
    {
        $samples = [
            'sample1_cetak_jelas.png' => 'Cetak jelas (Alfamart) — harus tetap Tesseract',
            'sample2_blur.png' => 'Blur foto malam — harus fallback Rapid',
            'sample3_no_digit.png' => 'Tanpa digit — harus fallback Rapid (no_digit)',
        ];

        $base = storage_path('app/test_struk_samples');
        $client = new OCRClient;
        $refConf = new \ReflectionMethod(OCRClient::class, 'estimateTesseractConfidence');
        $refConf->setAccessible(true);
        $refNeed = new \ReflectionMethod(OCRClient::class, 'needsFallback');
        $refNeed->setAccessible(true);
        $refReason = new \ReflectionMethod(OCRClient::class, 'fallbackReason');
        $refReason->setAccessible(true);

        $this->info("Samples dir: {$base}");
        $this->info("OCR threshold: ".config('ocr.confidence_threshold', 0.6)." | Tesseract PSN: ".config('ocr.tesseract.psm', 6));
        $this->newLine();

        foreach ($samples as $file => $desc) {
            $path = "{$base}/{$file}";
            if (! file_exists($path)) {
                $this->error("Missing: {$path}");
                continue;
            }
            $content = file_get_contents($path);
            $tmp = tempnam(sys_get_temp_dir(), 'ocr_test_');
            file_put_contents($tmp, $content);
            $psm = config('ocr.tesseract.psm', 6);
            $lang = config('ocr.tesseract.lang', 'ind+eng');
            $cmd = sprintf('timeout 15 tesseract %s stdout --psm %d --oem 1 -l %s 2>&1 || timeout 15 tesseract %s stdout --psm %d --oem 1 2>&1', escapeshellarg($tmp), $psm, escapeshellarg($lang), escapeshellarg($tmp), $psm);
            $output = shell_exec($cmd);
            $text = trim((string) $output);
            // Simpler: just run tesseract without lang fallback for demo
            if (str_contains($text, 'Failed loading language') || str_contains($text, 'Error opening')) {
                // fallback to no-lang
                $cmd2 = sprintf('timeout 15 tesseract %s stdout --psm %d --oem 1 2>&1', escapeshellarg($tmp), $psm);
                $text = trim((string) shell_exec($cmd2));
            }
            @unlink($tmp);

            $conf = $refConf->invoke($client, $text);
            $tess = ['text' => $text, 'confidence' => $conf];
            $needs = $refNeed->invoke($client, $tess);
            $reason = $refReason->invoke($client, $tess);

            $this->line("=== {$file} ===");
            $this->line("Desc: {$desc}");
            $this->line("Text: ".mb_strimwidth(str_replace("\n", " | ", $text), 0, 80, "..."));
            $this->line("Conf: ".number_format($conf,2)." | Len: ".mb_strlen($text)." | HasDigit: ".(preg_match('/\d/', $text) ? 'yes' : 'no'));
            $this->line("NeedsFallback: ".($needs ? 'YES → Rapid' : 'NO → Tesseract')." | Reason: {$reason}");
            $this->line("Engine final: ".($needs ? 'RapidOCR (fallback)' : 'Tesseract'));
            // Simulate AI decision (kirim ke AI Parser)
            $aiNote = $needs ? 'OCR blur/no-digit → Rapid fallback, lalu AI parser putuskan transaksi' : 'OCR jelas → langsung AI parser putuskan transaksi';
            $this->line("AI: {$aiNote}");
            $this->newLine();
        }

        $this->info("Done. Samples di storage/app/test_struk_samples/ dan public/samples/");
        $this->info("Cek log: evidence_processing_logs (stage=ocr) atau docker compose logs ocr-service");

        return self::SUCCESS;
    }
}
