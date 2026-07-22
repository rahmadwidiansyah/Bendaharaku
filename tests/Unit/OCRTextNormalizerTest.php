<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Evidence\OCRTextNormalizer;
use Tests\TestCase;

class OCRTextNormalizerTest extends TestCase
{
    private OCRTextNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('ocr_normalizer', require base_path('config/ocr_normalizer.php'));
        $this->normalizer = new OCRTextNormalizer;
    }

    // ── Currency Tests ───────────────────────────────────────────────

    public function test_normalize_currency_rpl(): void
    {
        $result = $this->normalizer->normalize('Rpl 25.000');
        $this->assertStringContainsString('Rp 25.000', $result['normalized']);
    }

    public function test_normalize_currency_rp_dot(): void
    {
        $result = $this->normalizer->normalize('Rp. 50.000');
        $this->assertStringContainsString('Rp 50.000', $result['normalized']);
    }

    public function test_normalize_currency_rp_uppercase(): void
    {
        $result = $this->normalizer->normalize('RP 100.000');
        $this->assertStringContainsString('Rp 100.000', $result['normalized']);
    }

    public function test_normalize_currency_idr(): void
    {
        $result = $this->normalizer->normalize('IDR 250.000');
        $this->assertStringContainsString('Rp 250.000', $result['normalized']);
    }

    // ── Wallet Tests ─────────────────────────────────────────────────

    public function test_normalize_wallet_seabank(): void
    {
        $result = $this->normalizer->normalize('Transfer ke Sea BanK');
        $this->assertStringContainsString('SeaBank', $result['normalized']);
    }

    public function test_normalize_wallet_shopeepay(): void
    {
        $result = $this->normalizer->normalize('Shopee Pay Balance');
        $this->assertStringContainsString('ShopeePay', $result['normalized']);
    }

    public function test_normalize_wallet_gopay(): void
    {
        $result = $this->normalizer->normalize('Go Pay transfer');
        $this->assertStringContainsString('GoPay', $result['normalized']);
    }

    public function test_normalize_wallet_ovo_typo(): void
    {
        $result = $this->normalizer->normalize('OV0 balance');
        $this->assertStringContainsString('OVO', $result['normalized']);
    }

    public function test_normalize_wallet_linkaja(): void
    {
        $result = $this->normalizer->normalize('Link Aja transfer');
        $this->assertStringContainsString('LinkAja', $result['normalized']);
    }

    // ── Number Context Tests ─────────────────────────────────────────

    public function test_normalize_number_context_o_to_0(): void
    {
        $result = $this->normalizer->normalize('Rp 25.OOO');
        // O in numeric context should be converted to 0
        $this->assertMatchesRegularExpression('/25\.[0-9]{3}/', $result['normalized']);
    }

    public function test_normalize_number_context_l_to_1(): void
    {
        $result = $this->normalizer->normalize('Total: lO.OOO');
        $this->assertMatchesRegularExpression('/10\.[0-9]{3}/', $result['normalized']);
    }

    // ── Reference Number Tests ───────────────────────────────────────

    public function test_normalize_reference_with_spaces(): void
    {
        $result = $this->normalizer->normalize('REF: ABC 123 456');
        $this->assertStringContainsString('ABC123456', $result['normalized']);
    }

    // ── Whitespace Tests ─────────────────────────────────────────────

    public function test_normalize_double_space(): void
    {
        $result = $this->normalizer->normalize('Hello  World');
        $this->assertStringContainsString('Hello World', $result['normalized']);
    }

    public function test_normalize_tab(): void
    {
        $result = $this->normalizer->normalize("Hello\tWorld");
        $this->assertStringContainsString('Hello World', $result['normalized']);
    }

    public function test_normalize_space_before_period(): void
    {
        $result = $this->normalizer->normalize('Hello .');
        $this->assertStringContainsString('Hello.', $result['normalized']);
    }

    public function test_normalize_space_before_comma(): void
    {
        $result = $this->normalizer->normalize('Hello ,');
        $this->assertStringContainsString('Hello,', $result['normalized']);
    }

    // ── Noise Tests ──────────────────────────────────────────────────

    public function test_normalize_noise_dashes(): void
    {
        $result = $this->normalizer->normalize("Hello\n---\nWorld");
        $this->assertStringNotContainsString('---', $result['normalized']);
        $this->assertStringContainsString('Hello', $result['normalized']);
        $this->assertStringContainsString('World', $result['normalized']);
    }

    public function test_normalize_noise_equals(): void
    {
        $result = $this->normalizer->normalize("Line1\n===\nLine2");
        $this->assertStringNotContainsString('===', $result['normalized']);
    }

    // ── Unicode Tests ────────────────────────────────────────────────

    public function test_normalize_unicode_nbsp(): void
    {
        $result = $this->normalizer->normalize("Hello\xC2\xA0World");
        $this->assertStringContainsString('Hello World', $result['normalized']);
    }

    // ── Edge Cases ───────────────────────────────────────────────────

    public function test_normalize_empty_string(): void
    {
        $result = $this->normalizer->normalize('');
        $this->assertEquals('', $result['normalized']);
        $this->assertEquals(0, $result['changes']);
    }

    public function test_normalize_whitespace_only(): void
    {
        $result = $this->normalizer->normalize("   \n\n  ");
        $this->assertEquals('', $result['normalized']);
    }

    public function test_normalize_returns_changes_count(): void
    {
        $result = $this->normalizer->normalize('Rpl 25.OOO');
        $this->assertGreaterThan(0, $result['changes']);
    }

    public function test_normalize_returns_duration(): void
    {
        $result = $this->normalizer->normalize('Test');
        $this->assertIsInt($result['duration_ms']);
        $this->assertGreaterThanOrEqual(0, $result['duration_ms']);
    }
}
