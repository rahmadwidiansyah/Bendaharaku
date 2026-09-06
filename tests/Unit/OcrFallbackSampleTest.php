<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Evidence\OCRClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OcrFallbackSampleTest extends TestCase
{
    use RefreshDatabase;

    private OCRClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new OCRClient;
    }

    private function estimateConfidence(string $text): float
    {
        $ref = new \ReflectionMethod(OCRClient::class, 'estimateTesseractConfidence');
        $ref->setAccessible(true);

        return $ref->invoke($this->client, $text);
    }

    private function needsFallback(?array $tess): bool
    {
        $ref = new \ReflectionMethod(OCRClient::class, 'needsFallback');
        $ref->setAccessible(true);

        return $ref->invoke($this->client, $tess);
    }

    private function fallbackReason(?array $tess): string
    {
        $ref = new \ReflectionMethod(OCRClient::class, 'fallbackReason');
        $ref->setAccessible(true);

        return $ref->invoke($this->client, $tess);
    }

    /** @test */
    public function test_sample_1_cetak_jelas_tetap_tesseract(): void
    {
        // Struk Alfamart cetak jelas — OCR Tesseract harusnya confidence tinggi, tidak fallback
        $text = "ALFAMART\nJl. Sudirman No.10\nTOTAL Rp 25.000\nTunai Rp 50.000\nKembali Rp 25.000\nTerima kasih";
        $conf = $this->estimateConfidence($text);
        $tess = ['text' => $text, 'confidence' => $conf];

        $this->assertGreaterThanOrEqual(0.6, $conf, "Struk cetak jelas harus >=0.6, got $conf");
        $this->assertFalse($this->needsFallback($tess), 'Struk jelas tidak boleh fallback ke Rapid');
        $this->assertStringContainsString('total', strtolower($text));
    }

    /** @test */
    public function test_sample_2_blur_rendah_fallback_rapid(): void
    {
        // Struk foto malam blur — text pendek tanpa digit, confidence rendah
        $text = "Alf\nTo blur";
        $conf = $this->estimateConfidence($text);
        $tess = ['text' => $text, 'confidence' => $conf];

        $this->assertLessThan(0.6, $conf, "Struk blur harus <0.6, got $conf");
        $this->assertTrue($this->needsFallback($tess), 'Struk blur harus fallback ke Rapid');
        $this->assertStringStartsWith('tesseract_low_conf', $this->fallbackReason($tess));
    }

    /** @test */
    public function test_sample_3_tanpa_digit_fallback_rapid(): void
    {
        // Tesseract baca header doang tanpa angka — harus fallback walau confidence medium
        $text = "BAYAR\nTerima kasih sudah belanja\n";
        $conf = $this->estimateConfidence($text);
        $tess = ['text' => $text, 'confidence' => $conf];

        // confidence mungkin 0.6+ tapi tanpa digit → tetap fallback
        $needs = $this->needsFallback($tess);
        $this->assertTrue($needs, 'Tanpa digit harus fallback walau conf '.number_format($conf, 2));
        $reason = $this->fallbackReason($tess);
        $this->assertTrue(str_contains($reason, 'no_digit') || str_contains($reason, 'too_short') || str_contains($reason, 'low_conf'));
    }

    /** @test */
    public function test_threshold_060_boundary(): void
    {
        // Boundary check: 0.60 persis tidak fallback, 0.59 fallback
        $ok = ['text' => str_repeat('A', 25).' Rp 10000', 'confidence' => 0.60];
        $fail = ['text' => str_repeat('A', 25).' Rp 10000', 'confidence' => 0.59];

        $this->assertFalse($this->needsFallback($ok), '0.60 tidak fallback');
        $this->assertTrue($this->needsFallback($fail), '0.59 harus fallback');
    }
}
