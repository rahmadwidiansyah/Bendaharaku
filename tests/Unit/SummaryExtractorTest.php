<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Evidence\Parsers\Extractors\SummaryExtractor;
use Tests\TestCase;

class SummaryExtractorTest extends TestCase
{
    private SummaryExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('shopping_parser.summary_line_patterns', [
            '/^(?:grand\s*)?total/i',
            '/^sub\s*total/i',
            '/^ppn/i',
            '/^pajak/i',
            '/^diskon/i',
            '/^discount/i',
            '/^service\s*charge/i',
            '/^bayar/i',
            '/^tunai/i',
            '/^cash/i',
            '/^kembalian/i',
            '/^change/i',
        ]);
        $this->extractor = new SummaryExtractor;
    }

    public function test_extract_total(): void
    {
        $text = "INDOMARET\nAQUA 8.000\nTOTAL\n23.000";
        $result = $this->extractor->extract($text);

        $this->assertEquals(23000.0, $result['total']);
        $this->assertGreaterThanOrEqual(0.8, $result['confidence']);
    }

    public function test_extract_subtotal_and_total(): void
    {
        $text = "INDOMARET\nSUBTOTAL\n20.000\nPPN\n2.000\nTOTAL\n22.000";
        $result = $this->extractor->extract($text);

        $this->assertEquals(20000.0, $result['subtotal']);
        $this->assertEquals(2000.0, $result['tax']);
        $this->assertEquals(22000.0, $result['total']);
    }

    public function test_extract_discount(): void
    {
        $text = "INDOMARET\nTOTAL\n23.000\nDISKON\n3.000\nBAYAR\n20.000";
        $result = $this->extractor->extract($text);

        $this->assertEquals(3000.0, $result['discount']);
        $this->assertEquals(23000.0, $result['total']);
    }

    public function test_extract_with_rp_prefix(): void
    {
        $text = "INDOMARET\nTotal: Rp 23.000";
        $result = $this->extractor->extract($text);

        $this->assertEquals(23000.0, $result['total']);
    }

    public function test_extract_empty(): void
    {
        $result = $this->extractor->extract('');
        $this->assertNull($result['total']);
        $this->assertEquals(0.0, $result['confidence']);
    }

    public function test_extract_service_charge(): void
    {
        $text = "RESTO\nSUBTOTAL\n50.000\nSERVICE CHARGE\n5.000\nTOTAL\n55.000";
        $result = $this->extractor->extract($text);

        $this->assertEquals(50000.0, $result['subtotal']);
        $this->assertEquals(5000.0, $result['service_charge']);
        $this->assertEquals(55000.0, $result['total']);
    }
}
