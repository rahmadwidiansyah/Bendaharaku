<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Evidence\Parsers\Extractors\ItemExtractor;
use Tests\TestCase;

class ItemExtractorTest extends TestCase
{
    private ItemExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('shopping_parser.summary_keywords', [
            'total', 'subtotal', 'sub total', 'grand total',
            'ppn', 'pajak', 'tax', 'pph',
            'diskon', 'discount', 'potongan',
            'service charge', 'service',
            'bayar', 'payment', 'tunai', 'cash',
            'kembalian', 'change', 'received',
        ]);
        $this->app['config']->set('shopping_parser.skip_keywords', [
            'total', 'subtotal', 'ppn', 'pajak', 'diskon', 'discount',
            'service charge', 'bayar', 'payment', 'tunai', 'cash',
            'kembalian', 'change', 'terima kasih', 'thank you',
            'tanggal', 'date', 'kasir', 'cashier', 'no', 'nomor',
        ]);
        $this->app['config']->set('shopping_parser.item_patterns', [
            '/^(.+?)\s+(\d+)\s*x\s*(?:rp\.?\s*)?([\d.,]+)\s+(?:rp\.?\s*)?([\d.,]+)$/i',
            '/^(.+?)\s+(?:rp\.?\s*)?([\d.,]+)$/i',
            '/^(.+?)\s+(\d+)\s*x\s*(?:rp\.?\s*)?([\d.,]+)$/i',
        ]);
        $this->extractor = new ItemExtractor;
    }

    public function test_extract_items_with_qty_and_total(): void
    {
        $text = "INDOMARET\nAQUA 600ML\n2 x 4.000\n8.000\nROTI\n1 x 15.000\n15.000\nTOTAL\n23.000";
        $result = $this->extractor->extract($text);

        $this->assertGreaterThanOrEqual(1, count($result['items']));
        $this->assertGreaterThanOrEqual(0.7, $result['confidence']);

        // Check first item
        $firstItem = $result['items'][0];
        $this->assertNotEmpty($firstItem->name);
        $this->assertGreaterThan(0, $firstItem->total);
    }

    public function test_extract_items_simple(): void
    {
        $text = "INDOMARET\nAQUA 600ML 8.000\nROTI 15.000\nTOTAL 23.000";
        $result = $this->extractor->extract($text);

        $this->assertGreaterThanOrEqual(1, count($result['items']));
    }

    public function test_skip_summary_lines(): void
    {
        $text = "TOTAL\n23.000\nSUBTOTAL\n20.000\nPPN\n2.000";
        $result = $this->extractor->extract($text);

        // Summary lines should not be extracted as items
        $this->assertEmpty($result['items'], 'Summary lines should not be extracted as items');
    }

    public function test_extract_empty_text(): void
    {
        $result = $this->extractor->extract('');
        $this->assertEmpty($result['items']);
        $this->assertEquals(0.0, $result['confidence']);
    }

    public function test_extract_single_item(): void
    {
        $text = "INDOMARET\nAQUA 600ML 4.000\nTOTAL 4.000";
        $result = $this->extractor->extract($text);

        $this->assertGreaterThanOrEqual(1, count($result['items']));
    }
}
