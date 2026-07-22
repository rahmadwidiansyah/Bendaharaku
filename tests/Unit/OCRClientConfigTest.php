<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Evidence\OCRClient;
use InvalidArgumentException;
use Tests\TestCase;

class OCRClientConfigTest extends TestCase
{
    private array $originalConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConfig = config('ocr');
    }

    protected function tearDown(): void
    {
        config(['ocr' => $this->originalConfig]);
        parent::tearDown();
    }

    public function test_ocr_timeout_is_integer(): void
    {
        config(['ocr.timeout' => 30]);
        $this->assertIsInt(config('ocr.timeout'));
    }

    public function test_ocr_connect_timeout_is_integer(): void
    {
        config(['ocr.connect_timeout' => 5]);
        $this->assertIsInt(config('ocr.connect_timeout'));
    }

    public function test_ocr_confidence_threshold_is_float(): void
    {
        config(['ocr.confidence_threshold' => 0.8]);
        $this->assertIsFloat(config('ocr.confidence_threshold'));
    }

    public function test_ocr_client_can_be_created_with_valid_config(): void
    {
        config([
            'ocr.url' => 'http://ocr-service:8000',
            'ocr.timeout' => 30,
            'ocr.connect_timeout' => 5,
            'ocr.retry' => 3,
            'ocr.retry_delay' => 1000,
            'ocr.confidence_threshold' => 0.8,
            'ocr.max_image_size_mb' => 10,
        ]);

        $client = new OCRClient;
        $this->assertInstanceOf(OCRClient::class, $client);
    }

    public function test_constructor_fails_on_empty_url(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OCR host is not configured');

        config(['ocr.url' => '']);
        new OCRClient;
    }

    public function test_constructor_fails_on_zero_timeout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OCR timeout must be greater than zero');

        config(['ocr.timeout' => 0]);
        new OCRClient;
    }

    public function test_constructor_fails_on_negative_retry(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OCR retry must not be negative');

        config(['ocr.retry' => -1]);
        new OCRClient;
    }

    public function test_constructor_fails_on_invalid_confidence_threshold(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OCR confidence threshold must be between 0 and 1');

        config(['ocr.confidence_threshold' => 1.5]);
        new OCRClient;
    }
}
