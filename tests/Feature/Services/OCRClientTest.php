<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Evidence;
use App\Services\Evidence\OCRClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class OCRClientTest extends TestCase
{
    public function test_extract_sends_file_multipart_field_to_ocr_service(): void
    {
        config([
            'ocr.url' => 'http://ocr-service:8000',
            'ocr.extract_endpoint' => '/ocr/extract',
            'ocr.engine' => 'rapid',
            'filesystems.disks.evidence' => [
                'driver' => 'local',
                'root' => '/tmp/opencode/ocr-client-test',
            ],
        ]);

        Log::spy();

        $evidence = Mockery::mock(Evidence::class)->makePartial();
        $evidence->id = 1;
        $evidence->uuid = 'test-uuid';
        $evidence->disk = 'evidence';
        $evidence->path = 'evidence/test.jpg';
        $evidence->original_name = 'test.jpg';
        $evidence->mime_type = 'image/jpeg';

        Storage::disk('evidence')->put($evidence->path, 'fake-image-bytes');

        $evidence->shouldReceive('update')
            ->once()
            ->with([
                'ocr_text' => 'OCR OK',
                'ocr_engine' => 'PaddleOCR',
                'ocr_duration_ms' => 123,
                'ocr_version' => '2.0-tess-rapid',
            ])
            ->andReturnTrue();

        Http::fake([
            'http://ocr-service:8000/ocr/extract' => Http::response([
                'success' => true,
                'text' => 'OCR OK',
                'processing_time_ms' => 123,
                'engine' => 'PaddleOCR',
            ]),
        ]);

        $result = app(OCRClient::class)->extract($evidence);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://ocr-service:8000/ocr/extract'
                && str_contains($request->body(), 'name="file"')
                && ! str_contains($request->body(), 'name="image"');
        });

        $this->assertSame('OCR OK', $result['text']);
        $this->assertSame('PaddleOCR', $result['engine']);
        $this->assertSame(123, $result['processing_time_ms']);
    }
}
