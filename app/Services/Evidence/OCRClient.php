<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Models\Evidence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class OCRClient
{
    private string $baseUrl;

    private int $timeout;

    private int $connectTimeout;

    private int $retry;

    private int $retryDelay;

    private string $engine;

    private string $fallback;

    private string $extractEndpoint;

    private float $confidenceThreshold;

    private float $maxImageSizeMb;

    private bool $debug;

    private int $tesseractPsm;

    private int $tesseractOem;

    private string $tesseractLang;

    private int $tesseractTimeout;

    private int $minTextLen;

    public function __construct()
    {
        $this->baseUrl = $this->validateUrl(config('ocr.url', 'http://ocr-service:8000'));
        $this->timeout = $this->validatePositiveInt(config('ocr.timeout', 30), 'OCR timeout');
        $this->connectTimeout = $this->validatePositiveInt(config('ocr.connect_timeout', 5), 'OCR connect timeout');
        $this->retry = $this->validateNonNegativeInt(config('ocr.retry', 3), 'OCR retry');
        $this->retryDelay = $this->validateNonNegativeInt(config('ocr.retry_delay', 1000), 'OCR retry delay');
        $this->engine = strtolower((string) config('ocr.engine', 'auto'));
        $this->fallback = strtolower((string) config('ocr.fallback', 'rapid'));
        $this->extractEndpoint = config('ocr.extract_endpoint', '/ocr/extract');
        $this->confidenceThreshold = $this->validateConfidence(config('ocr.confidence_threshold', 0.6));
        $this->maxImageSizeMb = $this->validatePositiveFloat(config('ocr.max_image_size_mb', 10.0), 'OCR max image size');
        $this->debug = (bool) config('ocr.debug', false);
        $this->tesseractPsm = (int) config('ocr.tesseract.psm', 6);
        $this->tesseractOem = (int) config('ocr.tesseract.oem', 1);
        $this->tesseractLang = (string) config('ocr.tesseract.lang', 'ind+eng');
        $this->tesseractTimeout = (int) config('ocr.tesseract.timeout', 15);
        $this->minTextLen = (int) config('ocr.tesseract.min_text_len', 20);
    }

    public function extract(Evidence $evidence): array
    {
        $start = microtime(true);

        $filePath = $evidence->path;
        $disk = $evidence->disk;

        if (! Storage::disk($disk)->exists($filePath)) {
            throw new RuntimeException("Evidence file not found: {$filePath}");
        }

        $fileContents = Storage::disk($disk)->get($filePath);
        $fileSize = strlen($fileContents);

        if ($fileSize > $this->maxImageSizeMb * 1024 * 1024) {
            throw new RuntimeException(sprintf(
                'Image size %.2f MB exceeds maximum of %.2f MB',
                $fileSize / (1024 * 1024),
                $this->maxImageSizeMb,
            ));
        }

        $mimeType = $evidence->mime_type;
        $originalName = $evidence->original_name;

        if ($this->debug) {
            Log::info('OCR request started', [
                'evidence_id' => $evidence->id,
                'uuid' => $evidence->uuid,
                'file_size' => $fileSize,
                'engine_mode' => $this->engine,
            ]);
        }

        $result = null;
        $fallbackReason = null;

        // Engine selection: auto -> tesseract primary, rapid fallback
        if ($this->engine === 'tesseract') {
            $result = $this->tryTesseract($fileContents, $evidence);
        } elseif ($this->engine === 'rapid') {
            $result = $this->tryRapid($fileContents, $mimeType, $originalName, $evidence);
        } else {
            // auto: tesseract dulu
            $tess = $this->tryTesseract($fileContents, $evidence);
            if ($tess !== null && ! $this->needsFallback($tess)) {
                $result = $tess;
            } else {
                $fallbackReason = $this->fallbackReason($tess);
                Log::info('OCR tesseract fallback to rapid', [
                    'evidence_id' => $evidence->id,
                    'reason' => $fallbackReason,
                    'tesseract_text_len' => $tess ? strlen($tess['text'] ?? '') : 0,
                    'tesseract_conf' => $tess['confidence'] ?? 0,
                ]);
                $result = $this->tryRapid($fileContents, $mimeType, $originalName, $evidence);
                if ($result !== null) {
                    $result['fallback_reason'] = $fallbackReason;
                } else {
                    // jika rapid juga gagal, pakai tesseract seadanya
                    $result = $tess;
                }
            }
        }

        if ($result === null || ($result['text'] ?? '') === '') {
            throw new RuntimeException('OCR failed: no text extracted from both engines');
        }

        $ocrText = $result['text'];
        $processingTimeMs = (int) ($result['processing_time_ms'] ?? 0);
        $engine = $result['engine'] ?? $this->engine;

        $evidence->update([
            'ocr_text' => $ocrText,
            'ocr_engine' => $engine,
            'ocr_duration_ms' => $processingTimeMs,
            'ocr_version' => '2.0-tess-rapid',
        ]);

        // Save to evidence_processing_logs for observability (Privacy Logs) — pakai status_before/after sesuai migration kanonik
        try {
            DB::table('evidence_processing_logs')->insert([
                'evidence_id' => $evidence->id,
                'stage' => 'OCR',
                'status_before' => $evidence->status->value ?? null,
                'status_after' => 'OCR_SUCCESS',
                'duration_ms' => $processingTimeMs,
                'message' => $fallbackReason ? "fallback: {$fallbackReason}" : "engine: {$engine}",
                'metadata' => json_encode([
                    'engine' => $engine,
                    'fallback_reason' => $fallbackReason,
                    'confidence' => $result['confidence'] ?? null,
                    'text_len' => strlen($ocrText),
                    'processing_time_ms' => $processingTimeMs,
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to log OCR evidence_processing_logs: '.$e->getMessage());
        }

        $elapsed = (int) ((microtime(true) - $start) * 1000);

        Log::info('OCR request completed', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'ocr_duration_ms' => $processingTimeMs,
            'total_elapsed_ms' => $elapsed,
            'engine' => $engine,
            'fallback_reason' => $fallbackReason,
            'text_length' => strlen($ocrText),
            'confidence' => $result['confidence'] ?? null,
        ]);

        return [
            'text' => $ocrText,
            'processing_time_ms' => $processingTimeMs,
            'engine' => $engine,
            'confidence' => $result['confidence'] ?? null,
            'fallback_reason' => $fallbackReason,
        ];
    }

    private function tryTesseract(string $fileContents, Evidence $evidence): ?array
    {
        $start = microtime(true);
        $tmpFile = tempnam(sys_get_temp_dir(), 'ocr_tess_');
        $tmpOut = $tmpFile . '.txt';

        try {
            file_put_contents($tmpFile, $fileContents);

            $psm = $this->tesseractPsm;
            $oem = $this->tesseractOem;
            $langsToTry = [$this->tesseractLang, 'eng', 'osd', ''];
            $output = null;
            $usedLang = $this->tesseractLang;

            foreach ($langsToTry as $langTry) {
                $langArg = $langTry !== '' ? ' -l '.escapeshellarg($langTry) : '';
                $cmd = sprintf(
                    'timeout %d tesseract %s stdout --psm %d --oem %d%s 2>&1',
                    $this->tesseractTimeout,
                    escapeshellarg($tmpFile),
                    $psm,
                    $oem,
                    $langArg
                );
                $output = shell_exec($cmd);
                if ($output !== null && ! str_contains($output, 'Failed loading language') && ! str_contains($output, "Could not initialize")) {
                    $usedLang = $langTry ?: 'default';
                    break;
                }
                Log::warning("Tesseract lang {$langTry} failed, trying next", ['evidence_id' => $evidence->id, 'output' => substr((string) $output, 0, 200)]);
                $output = null;
            }

            if ($output === null) {
                Log::warning('Tesseract shell_exec returned null after lang fallback', ['evidence_id' => $evidence->id]);
                return null;
            }

            // If output still contains error marker, treat as failure
            if (str_contains($output, 'Error opening data file') || str_contains($output, 'Failed loading language')) {
                return null;
            }

            $text = trim((string) $output);
            $elapsedMs = (int) ((microtime(true) - $start) * 1000);
            $confidence = $this->estimateTesseractConfidence($text);

            return [
                'text' => $text,
                'processing_time_ms' => $elapsedMs,
                'engine' => 'Tesseract'.($usedLang !== $this->tesseractLang ? "({$usedLang})" : ''),
                'confidence' => $confidence,
            ];
        } catch (\Throwable $e) {
            Log::warning('Tesseract OCR failed: '.$e->getMessage(), ['evidence_id' => $evidence->id]);
            return null;
        } finally {
            @unlink($tmpFile);
            @unlink($tmpOut);
        }
    }

    private function tryRapid(string $fileContents, string $mimeType, string $originalName, Evidence $evidence): ?array
    {
        $url = rtrim($this->baseUrl, '/').$this->extractEndpoint;

        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->retry($this->retry, $this->retryDelay)
                ->attach('file', $fileContents, $originalName, ['Content-Type' => $mimeType])
                ->post($url);

            if (! $response->successful()) {
                Log::warning('RapidOCR HTTP error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $data = $response->json();
            if (! ($data['success'] ?? false)) {
                return null;
            }

            return [
                'text' => $data['text'] ?? '',
                'processing_time_ms' => (int) ($data['processing_time_ms'] ?? 0),
                'engine' => $data['engine'] ?? 'RapidOCR',
                'confidence' => 0.85, // RapidOCR confidence approximated
            ];
        } catch (\Throwable $e) {
            Log::warning('RapidOCR request failed: '.$e->getMessage(), ['evidence_id' => $evidence->id]);
            return null;
        }
    }

    private function estimateTesseractConfidence(string $text): float
    {
        $len = mb_strlen(trim($text));
        if ($len === 0) return 0.0;
        // Heuristik: panjang + ada digit + ada kata kunci struk
        $hasDigit = (bool) preg_match('/\d/', $text);
        $hasKeyword = (bool) preg_match('/\b(total|nominal|jumlah|bayar|transfer|rp|idr)\b/iu', $text);
        $score = 0.5;
        if ($len >= $this->minTextLen) $score += 0.2;
        if ($hasDigit) $score += 0.15;
        if ($hasKeyword) $score += 0.1;
        if ($len > 100) $score += 0.05;
        return min(0.95, $score);
    }

    private function needsFallback(?array $tessResult): bool
    {
        if ($tessResult === null) return true;
        $text = trim($tessResult['text'] ?? '');
        $conf = (float) ($tessResult['confidence'] ?? 0);
        if ($conf < $this->confidenceThreshold) return true;
        if (mb_strlen($text) < $this->minTextLen) return true;
        if (! preg_match('/\d/', $text)) return true;
        return false;
    }

    private function fallbackReason(?array $tessResult): string
    {
        if ($tessResult === null) return 'tesseract_null';
        $text = trim($tessResult['text'] ?? '');
        $conf = (float) ($tessResult['confidence'] ?? 0);
        if ($conf < $this->confidenceThreshold) return 'tesseract_low_conf_'.number_format($conf, 2);
        if (mb_strlen($text) < $this->minTextLen) return 'tesseract_too_short_'.mb_strlen($text);
        if (! preg_match('/\d/', $text)) return 'tesseract_no_digit';
        return 'unknown';
    }

    private function validateUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') throw new InvalidArgumentException('OCR host is not configured.');
        if (! filter_var($url, FILTER_VALIDATE_URL) && ! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = "http://{$url}";
        }
        if (! filter_var($url, FILTER_VALIDATE_URL)) throw new InvalidArgumentException("OCR URL is invalid: {$url}");
        return $url;
    }

    private function validatePositiveInt(mixed $value, string $label): int
    {
        $int = (int) $value;
        if ($int <= 0) throw new InvalidArgumentException("{$label} must be greater than zero, got {$int}");
        return $int;
    }

    private function validateNonNegativeInt(mixed $value, string $label): int
    {
        $int = (int) $value;
        if ($int < 0) throw new InvalidArgumentException("{$label} must not be negative, got {$int}");
        return $int;
    }

    private function validatePositiveFloat(mixed $value, string $label): float
    {
        $float = (float) $value;
        if ($float <= 0) throw new InvalidArgumentException("{$label} must be greater than zero, got {$float}");
        return $float;
    }

    private function validateConfidence(mixed $value): float
    {
        $float = (float) $value;
        if ($float < 0 || $float > 1) throw new InvalidArgumentException("OCR confidence threshold must be between 0 and 1, got {$float}");
        return $float;
    }
}
