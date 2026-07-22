<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\DocumentType;
use App\Evidence\Parsers\ShoppingReceiptParser;
use App\Models\Evidence;
use Mockery;
use Tests\TestCase;

class ShoppingReceiptParserTest extends TestCase
{
    private ShoppingReceiptParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('shopping_parser', require base_path('config/shopping_parser.php'));
        $this->parser = new ShoppingReceiptParser;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEvidence(string $ocrText): Evidence
    {
        $evidence = Mockery::mock(Evidence::class)->makePartial();
        $evidence->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $evidence->shouldReceive('getAttribute')->with('uuid')->andReturn('test-uuid');
        $evidence->shouldReceive('getAttribute')->with('normalized_text')->andReturn($ocrText);
        $evidence->shouldReceive('getAttribute')->with('ocr_text')->andReturn($ocrText);

        return $evidence;
    }

    // ── Indomaret ──────────────────────────────────────────────────

    public function test_parse_indomaret(): void
    {
        $ocrText = "INDOMARET\n"
            ."Jl. Sudirman No. 1 Jakarta\n"
            ."22/07/2026 09:30\n"
            ."Kasir: 001 Budi\n"
            ."No: 001234\n"
            ."AQUA 600ML\n"
            ."2 x 4.000\n"
            ."8.000\n"
            ."ROTI GANDUM\n"
            ."1 x 15.000\n"
            ."15.000\n"
            ."SUBTOTAL\n"
            ."23.000\n"
            ."PPN\n"
            ."2.300\n"
            ."TOTAL\n"
            ."25.300\n"
            ."TUNAI\n"
            ."30.000\n"
            ."KEMBALIAN\n"
            .'4.700';

        $evidence = $this->makeEvidence($ocrText);
        $result = $this->parser->parse($evidence);

        $this->assertEquals(DocumentType::ShoppingReceipt, $result->documentType);
        $this->assertEquals('Indomaret', $result->merchantName);
        $this->assertEquals(25300.0, $result->amount);
        $this->assertEquals(23000.0, $result->subtotal);
        $this->assertEquals(2300.0, $result->tax);
        $this->assertEquals('Tunai', $result->paymentMethod);
        $this->assertEquals('001234', $result->receiptNumber);
        $this->assertEquals('Budi', $result->cashier);
        $this->assertNotEmpty($result->items);
        $this->assertGreaterThan(0, $result->confidence);
    }

    // ── Alfamart ───────────────────────────────────────────────────

    public function test_parse_alfamart(): void
    {
        $ocrText = "ALFAMART\n"
            ."Jl. Gatot Subroto\n"
            ."22/07/2026 10:00\n"
            ."Kasir: Wati\n"
            ."No: 005678\n"
            ."INDOMIE GORENG\n"
            ."3 x 3.500\n"
            ."10.500\n"
            ."TOTAL\n"
            ."10.500\n"
            ."TUNAI\n"
            ."11.000\n"
            ."KEMBALIAN\n"
            .'500';

        $evidence = $this->makeEvidence($ocrText);
        $result = $this->parser->parse($evidence);

        $this->assertEquals('Alfamart', $result->merchantName);
        $this->assertEquals(10500.0, $result->amount);
        $this->assertEquals('Tunai', $result->paymentMethod);
        $this->assertNotEmpty($result->items);
    }

    // ── Super Indo ─────────────────────────────────────────────────

    public function test_parse_super_indo(): void
    {
        $ocrText = "SUPER INDO\n"
            ."Jl. Asia Afrika\n"
            ."22/07/2026 11:00\n"
            ."Kasir: Andi\n"
            ."apel merah\n"
            ."1 kg x 35.000\n"
            ."35.000\n"
            ."susu ultra\n"
            ."2 x 8.500\n"
            ."17.000\n"
            ."TOTAL\n"
            ."52.000\n"
            ."DEBIT\n"
            .'52.000';

        $evidence = $this->makeEvidence($ocrText);
        $result = $this->parser->parse($evidence);

        $this->assertEquals('Super Indo', $result->merchantName);
        $this->assertEquals(52000.0, $result->amount);
        $this->assertEquals('Debit', $result->paymentMethod);
        $this->assertNotEmpty($result->items);
    }

    // ── McDonald's ─────────────────────────────────────────────────

    public function test_parse_mcdonalds(): void
    {
        $ocrText = "McDonald's\n"
            ."Jl. Thamrin\n"
            ."22/07/2026 12:00\n"
            ."Kasir: Rina\n"
            ."No: M001\n"
            ."BIG MAC\n"
            ."1 x 55.000\n"
            ."55.000\n"
            ."COCA COLA\n"
            ."2 x 18.000\n"
            ."36.000\n"
            ."SUBTOTAL\n"
            ."91.000\n"
            ."PPN\n"
            ."9.100\n"
            ."TOTAL\n"
            ."100.100\n"
            ."QRIS\n"
            .'100.100';

        $evidence = $this->makeEvidence($ocrText);
        $result = $this->parser->parse($evidence);

        $this->assertEquals("McDonald's", $result->merchantName);
        $this->assertEquals(100100.0, $result->amount);
        $this->assertEquals(91000.0, $result->subtotal);
        $this->assertEquals(9100.0, $result->tax);
        $this->assertEquals('QRIS', $result->paymentMethod);
        $this->assertNotEmpty($result->items);
    }

    // ── Mixue ──────────────────────────────────────────────────────

    public function test_parse_mixue(): void
    {
        $ocrText = "MIXUE\n"
            ."Jl. Kemang Selatan\n"
            ."22/07/2026 14:00\n"
            ."Kasir: Sari\n"
            ."No: MX001\n"
            ."Ice Cream Orange\n"
            ."1 x 10.000\n"
            ."10.000\n"
            ."Bobatea Green Tea\n"
            ."1 x 22.000\n"
            ."22.000\n"
            ."TOTAL\n"
            ."32.000\n"
            ."GoPay\n"
            .'32.000';

        $evidence = $this->makeEvidence($ocrText);
        $result = $this->parser->parse($evidence);

        $this->assertEquals('Mixue', $result->merchantName);
        $this->assertEquals(32000.0, $result->amount);
        $this->assertEquals('GoPay', $result->paymentMethod);
        $this->assertNotEmpty($result->items);
    }

    // ── KFC ────────────────────────────────────────────────────────

    public function test_parse_kfc(): void
    {
        $ocrText = "KFC\n"
            ."Jl. Casablanca\n"
            ."22/07/2026 13:00\n"
            ."Kasir: Dina\n"
            ."No: K001\n"
            ."Paket Komplit\n"
            ."1 x 65.000\n"
            ."65.000\n"
            ."Es Teh\n"
            ."2 x 12.000\n"
            ."24.000\n"
            ."SUBTOTAL\n"
            ."89.000\n"
            ."PPN\n"
            ."8.900\n"
            ."TOTAL\n"
            ."97.900\n"
            ."KARTU KREDIT\n"
            .'97.900';

        $evidence = $this->makeEvidence($ocrText);
        $result = $this->parser->parse($evidence);

        $this->assertEquals('KFC', $result->merchantName);
        $this->assertEquals(97900.0, $result->amount);
        $this->assertEquals('Kredit', $result->paymentMethod);
        $this->assertNotEmpty($result->items);
    }

    // ── Edge Cases ─────────────────────────────────────────────────

    public function test_parse_empty_text(): void
    {
        $evidence = $this->makeEvidence('');
        $result = $this->parser->parse($evidence);

        $this->assertEquals(DocumentType::ShoppingReceipt, $result->documentType);
        $this->assertNull($result->merchantName);
        $this->assertNull($result->amount);
        $this->assertEmpty($result->items);
    }

    public function test_parse_returns_metadata(): void
    {
        $ocrText = "INDOMARET\nTOTAL\n23.000\nTUNAI\n25.000";
        $evidence = $this->makeEvidence($ocrText);
        $result = $this->parser->parse($evidence);

        $this->assertArrayHasKey('extractors', $result->metadata);
        $this->assertArrayHasKey('merchant', $result->metadata['extractors']);
        $this->assertArrayHasKey('summary', $result->metadata['extractors']);
        $this->assertArrayHasKey('items', $result->metadata['extractors']);
        $this->assertArrayHasKey('payment_method', $result->metadata['extractors']);
    }

    public function test_parse_with_normalized_text(): void
    {
        $ocrText = "INDOMARET\nTOTAL\n23.000";
        $evidence = $this->makeEvidence($ocrText);

        $result = $this->parser->parse($evidence);

        $this->assertNotNull($result);
    }

    public function test_parse_transaction_type_is_expense(): void
    {
        $ocrText = "INDOMARET\nTOTAL\n23.000";
        $evidence = $this->makeEvidence($ocrText);
        $result = $this->parser->parse($evidence);

        $this->assertEquals('EXPENSE', $result->transactionType);
    }

    public function test_parse_currency_is_idr(): void
    {
        $ocrText = "INDOMARET\nTOTAL\n23.000";
        $evidence = $this->makeEvidence($ocrText);
        $result = $this->parser->parse($evidence);

        $this->assertEquals('IDR', $result->currency);
    }
}
