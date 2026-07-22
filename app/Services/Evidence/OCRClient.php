<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Models\Evidence;
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

    private string $extractEndpoint;

    private float $confidenceThreshold;

    private float $maxImageSizeMb;

    private bool $debug;

    public function __construct()
    {
        $this->baseUrl = $this->validateUrl(config('ocr.url', 'http://ocr-service:8000'));
        $this->timeout = $this->validatePositiveInt(config('ocr.timeout', 30), 'OCR timeout');
        $this->connectTimeout = $this->validatePositiveInt(config('ocr.connect_timeout', 5), 'OCR connect timeout');
        $this->retry = $this->validateNonNegativeInt(config('ocr.retry', 3), 'OCR retry');
        $this->retryDelay = $this->validateNonNegativeInt(config('ocr.retry_delay', 1000), 'OCR retry delay');
        $this->engine = config('ocr.engine', 'PaddleOCR');
        $this->extractEndpoint = config('ocr.extract_endpoint', '/ocr/extract');
        $this->confidenceThreshold = $this->validateConfidence(config('ocr.confidence_threshold', 0.8));
        $this->maxImageSizeMb = $this->validatePositiveFloat(config('ocr.max_image_size_mb', 10.0), 'OCR max image size');
        $this->debug = (bool) config('ocr.debug', false);
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
                'mime_type' => $mimeType,
                'url' => $this->baseUrl,
                'timeout' => $this->timeout,
                'connect_timeout' => $this->connectTimeout,
            ]);
        }

        $url = rtrim($this->baseUrl, '/').$this->extractEndpoint;

        $response = Http::timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->retry($this->retry, $this->retryDelay)
            ->attach(
                'file',
                $fileContents,
                $originalName,
                ['Content-Type' => $mimeType]
            )
            ->post($url);

        if (! $response->successful()) {
            $error = $response->body();
            Log::error('OCR service returned error', [
                'evidence_id' => $evidence->id,
                'status' => $response->status(),
                'error' => $error,
            ]);

            throw new RuntimeException("OCR service error (HTTP {$response->status()}): {$error}");
        }

        $data = $response->json();

        if (! ($data['success'] ?? false)) {
            throw new RuntimeException('OCR service returned success=false');
        }

        $ocrText = $data['text'] ?? '';
        $processingTimeMs = (int) ($data['processing_time_ms'] ?? 0);
        $engine = $data['engine'] ?? $this->engine;

        $evidence->update([
            'ocr_text' => $ocrText,
            'ocr_engine' => $engine,
            'ocr_duration_ms' => $processingTimeMs,
            'ocr_version' => '1.0',
        ]);

        $elapsed = (int) ((microtime(true) - $start) * 1000);

        Log::info('OCR request completed', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'ocr_duration_ms' => $processingTimeMs,
            'total_elapsed_ms' => $elapsed,
            'engine' => $engine,
            'text_length' => strlen($ocrText),
            'line_count' => substr_count($ocrText, "\n") + 1,
        ]);

        return [
            'text' => $ocrText,
            'processing_time_ms' => $processingTimeMs,
            'engine' => $engine,
        ];
    }

    private function validateUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            throw new InvalidArgumentException('OCR host is not configured.');
        }

        if (! filter_var($url, FILTER_VALIDATE_URL) && ! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = "http://{$url}";
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("OCR URL is invalid: {$url}");
        }

        return $url;
    }

    private function validatePositiveInt(mixed $value, string $label): int
    {
        $int = (int) $value;

        if ($int <= 0) {
            throw new InvalidArgumentException("{$label} must be greater than zero, got {$int}");
        }

        return $int;
    }

    private function validateNonNegativeInt(mixed $value, string $label): int
    {
        $int = (int) $value;

        if ($int < 0) {
            throw new InvalidArgumentException("{$label} must not be negative, got {$int}");
        }

        return $int;
    }

    private function validatePositiveFloat(mixed $value, string $label): float
    {
        $float = (float) $value;

        if ($float <= 0) {
            throw new InvalidArgumentException("{$label} must be greater than zero, got {$float}");
        }

        return $float;
    }

    private function validateConfidence(mixed $value): float
    {
        $float = (float) $value;

        if ($float < 0 || $float > 1) {
            throw new InvalidArgumentException("OCR confidence threshold must be between 0 and 1, got {$float}");
        }

        return $float;
    }
}
