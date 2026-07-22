<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Evidence\Parsers\Extractors\MerchantExtractor;
use Tests\TestCase;

class MerchantExtractorTest extends TestCase
{
    private MerchantExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('shopping_parser.merchant_aliases', [
            'Indomaret' => ['indomaret', 'INDOMARET'],
            'Alfamart' => ['alfamart', 'ALFAMART'],
            'Super Indo' => ['super indo', 'SUPER INDO', 'Superindo'],
            'McDonald\'s' => ["mcdonald's", 'McDonalds'],
            'KFC' => ['kfc', 'KFC'],
            'Mixue' => ['mixue', 'MIXUE'],
            'Starbucks' => ['starbucks', 'STARBUCKS'],
        ]);
        $this->extractor = new MerchantExtractor;
    }

    public function test_extract_indomaret(): void
    {
        $result = $this->extractor->extract("INDOMARET\nJl. Sudirman No. 1\n22/07/2026 09:30");
        $this->assertEquals('Indomaret', $result['merchant_name']);
        $this->assertGreaterThanOrEqual(0.9, $result['confidence']);
    }

    public function test_extract_alfamart(): void
    {
        $result = $this->extractor->extract("ALFAMART\nJl. Gatot Subroto\n22/07/2026 10:00");
        $this->assertEquals('Alfamart', $result['merchant_name']);
        $this->assertGreaterThanOrEqual(0.9, $result['confidence']);
    }

    public function test_extract_super_indo(): void
    {
        $result = $this->extractor->extract("SUPER INDO\nJl. Asia Afrika\n22/07/2026 11:00");
        $this->assertEquals('Super Indo', $result['merchant_name']);
        $this->assertGreaterThanOrEqual(0.9, $result['confidence']);
    }

    public function test_extract_mcdonalds(): void
    {
        $result = $this->extractor->extract("McDonald's\nJl. Thamrin\n22/07/2026 12:00");
        $this->assertEquals("McDonald's", $result['merchant_name']);
        $this->assertGreaterThanOrEqual(0.9, $result['confidence']);
    }

    public function test_extract_kfc(): void
    {
        $result = $this->extractor->extract("KFC\nJl. Casablanca\n22/07/2026 13:00");
        $this->assertEquals('KFC', $result['merchant_name']);
        $this->assertGreaterThanOrEqual(0.9, $result['confidence']);
    }

    public function test_extract_mixue(): void
    {
        $result = $this->extractor->extract("MIXUE\nJl. Kemang\n22/07/2026 14:00");
        $this->assertEquals('Mixue', $result['merchant_name']);
        $this->assertGreaterThanOrEqual(0.9, $result['confidence']);
    }

    public function test_extract_unknown_merchant(): void
    {
        $result = $this->extractor->extract("Toko Bahagia\nJl. Merdeka\n22/07/2026 15:00");
        // Unknown merchant still gets a name from fallback
        $this->assertNotNull($result['merchant_name']);
        $this->assertGreaterThanOrEqual(0.5, $result['confidence']);
    }

    public function test_extract_empty_text(): void
    {
        $result = $this->extractor->extract('');
        $this->assertNull($result['merchant_name']);
        $this->assertEquals(0.0, $result['confidence']);
    }
}
