<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\DocumentType;
use App\Evidence\DTO\EvidenceData;
use App\Evidence\Parsers\QrisReceiptParser;
use App\Models\Evidence;
use Mockery;
use Tests\TestCase;

class QrisReceiptParserTest extends TestCase
{
    private QrisReceiptParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('qris_parser', require base_path('config/qris_parser.php'));
        $this->parser = new QrisReceiptParser;
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

    public function test_parse_qris_gopay(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."MIXUE PANDANARAN\n"
            ."Nominal\n"
            ."Rp25.000\n"
            ."Metode\n"
            ."GoPay\n"
            ."Ref\n"
            ."20260722123456\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'14:33';

        $result = $this->parser->parse($this->makeEvidence($ocrText));

        $this->assertInstanceOf(EvidenceData::class, $result);
        $this->assertEquals(DocumentType::QrisReceipt, $result->documentType);
        $this->assertEquals('Mixue', $result->merchantName);
        $this->assertEquals(25000, $result->amount);
        $this->assertEquals('GoPay', $result->walletName);
        $this->assertEquals('20260722123456', $result->referenceNumber);
        $this->assertEquals('QRIS', $result->paymentMethod);
        $this->assertEquals('2026-07-22', $result->date);
        $this->assertEquals('14:33', $result->time);
        $this->assertEquals('EXPENSE', $result->transactionType);
    }

    public function test_parse_qris_seabank(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."INDOMARET\n"
            ."Nominal\n"
            ."Rp50.000\n"
            ."Metode\n"
            ."SeaBank\n"
            ."Ref\n"
            ."20260722123457\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'10:15';

        $result = $this->parser->parse($this->makeEvidence($ocrText));

        $this->assertEquals('Indomaret', $result->merchantName);
        $this->assertEquals(50000, $result->amount);
        $this->assertEquals('SeaBank', $result->walletName);
        $this->assertEquals('20260722123457', $result->referenceNumber);
        $this->assertEquals('2026-07-22', $result->date);
        $this->assertEquals('10:15', $result->time);
    }

    public function test_parse_qris_ovo(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."ALFAMART\n"
            ."Nominal\n"
            ."Rp15.750\n"
            ."Metode\n"
            ."OVO\n"
            ."Ref\n"
            ."20260722123458\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'18:45';

        $result = $this->parser->parse($this->makeEvidence($ocrText));

        $this->assertEquals('Alfamart', $result->merchantName);
        $this->assertEquals(15750, $result->amount);
        $this->assertEquals('OVO', $result->walletName);
        $this->assertEquals('20260722123458', $result->referenceNumber);
        $this->assertEquals('2026-07-22', $result->date);
        $this->assertEquals('18:45', $result->time);
    }

    public function test_parse_qris_dana(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."SUPERINDO\n"
            ."Nominal\n"
            ."Rp125.000\n"
            ."Metode\n"
            ."DANA\n"
            ."Ref\n"
            ."20260722123459\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'12:00';

        $result = $this->parser->parse($this->makeEvidence($ocrText));

        $this->assertEquals('Superindo', $result->merchantName);
        $this->assertEquals(125000, $result->amount);
        $this->assertEquals('DANA', $result->walletName);
        $this->assertEquals('2026-07-22', $result->date);
        $this->assertEquals('12:00', $result->time);
    }

    public function test_parse_qris_shopeepay(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."STARBUCKS\n"
            ."Nominal\n"
            ."Rp85.000\n"
            ."Metode\n"
            ."ShopeePay\n"
            ."Ref\n"
            ."20260722123460\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'09:15';

        $result = $this->parser->parse($this->makeEvidence($ocrText));

        $this->assertEquals('Starbucks', $result->merchantName);
        $this->assertEquals(85000, $result->amount);
        $this->assertEquals('ShopeePay', $result->walletName);
        $this->assertEquals('20260722123460', $result->referenceNumber);
    }

    public function test_parse_qris_bca(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."MIXUE PANDANARAN\n"
            ."Nominal\n"
            ."Rp25.000\n"
            ."Metode\n"
            ."BCA\n"
            ."Ref\n"
            ."20260722123461\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'14:33';

        $result = $this->parser->parse($this->makeEvidence($ocrText));

        $this->assertEquals('Mixue', $result->merchantName);
        $this->assertEquals(25000, $result->amount);
        $this->assertEquals('BCA', $result->walletName);
        $this->assertEquals('20260722123461', $result->referenceNumber);
    }

    public function test_parse_qris_mandiri(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."ALFAMART\n"
            ."Nominal\n"
            ."Rp32.500\n"
            ."Metode\n"
            ."Mandiri\n"
            ."Ref\n"
            ."20260722123462\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'11:20';

        $result = $this->parser->parse($this->makeEvidence($ocrText));

        $this->assertEquals('Alfamart', $result->merchantName);
        $this->assertEquals(32500, $result->amount);
        $this->assertEquals('Mandiri', $result->walletName);
        $this->assertEquals('20260722123462', $result->referenceNumber);
    }

    public function test_parse_qris_bri(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."SHELL\n"
            ."Nominal\n"
            ."Rp500.000\n"
            ."Metode\n"
            ."BRI\n"
            ."Ref\n"
            ."20260722123463\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'16:45';

        $result = $this->parser->parse($this->makeEvidence($ocrText));

        $this->assertEquals('Shell', $result->merchantName);
        $this->assertEquals(500000, $result->amount);
        $this->assertEquals('BRI', $result->walletName);
        $this->assertEquals('20260722123463', $result->referenceNumber);
    }

    public function test_parse_qris_returns_evidence_data(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."KFC\n"
            ."Nominal\n"
            ."Rp75.000\n"
            ."Metode\n"
            ."OVO\n"
            ."Ref\n"
            ."20260722123464\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'13:00';

        $result = $this->parser->parse($this->makeEvidence($ocrText));

        $this->assertInstanceOf(EvidenceData::class, $result);
        $this->assertNotNull($result->documentType);
        $this->assertEquals(DocumentType::QrisReceipt, $result->documentType);
        $this->assertEquals('EXPENSE', $result->transactionType);
        $this->assertEquals('IDR', $result->currency);
        $this->assertEquals('QRIS', $result->paymentMethod);
    }

    public function test_parse_qris_returns_metadata(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."KFC\n"
            ."Nominal\n"
            ."Rp75.000\n"
            ."Metode\n"
            ."OVO\n"
            ."Ref\n"
            ."20260722123464\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'13:00';

        $result = $this->parser->parse($this->makeEvidence($ocrText));

        $this->assertNotEmpty($result->metadata);
        $this->assertArrayHasKey('extractors', $result->metadata);
        $this->assertArrayHasKey('merchant_confidence', $result->metadata);
        $this->assertArrayHasKey('wallet_confidence', $result->metadata);
        $this->assertArrayHasKey('amount_confidence', $result->metadata);
        $this->assertArrayHasKey('overall_confidence', $result->metadata);
    }

    public function test_parse_qris_with_normalized_text(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."KFC\n"
            ."Nominal\n"
            ."Rp75.000\n"
            ."Metode\n"
            ."OVO\n"
            ."Ref\n"
            ."20260722123464\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'13:00';

        $evidence = Mockery::mock(Evidence::class)->makePartial();
        $evidence->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $evidence->shouldReceive('getAttribute')->with('uuid')->andReturn('test-uuid');
        $evidence->shouldReceive('getAttribute')->with('normalized_text')->andReturn($ocrText);
        $evidence->shouldReceive('getAttribute')->with('ocr_text')->andReturn(null);

        $result = $this->parser->parse($evidence);
        $this->assertEquals('KFC', $result->merchantName);
        $this->assertEquals(75000, $result->amount);
    }

    public function test_parse_qris_transaction_type_is_expense(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."KFC\n"
            ."Nominal\n"
            ."Rp75.000\n"
            ."Metode\n"
            ."GoPay\n"
            ."Ref\n"
            ."20260722123464\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'13:00';

        $result = $this->parser->parse($this->makeEvidence($ocrText));

        $this->assertEquals('EXPENSE', $result->transactionType);
    }

    public function test_parse_qris_currency_is_idr(): void
    {
        $ocrText = "QRIS\n"
            ."PEMBAYARAN BERHASIL\n"
            ."Merchant\n"
            ."KFC\n"
            ."Nominal\n"
            ."Rp75.000\n"
            ."Metode\n"
            ."GoPay\n"
            ."Ref\n"
            ."20260722123464\n"
            ."Tanggal\n"
            ."22/07/2026\n"
            ."Jam\n"
            .'13:00';

        $result = $this->parser->parse($this->makeEvidence($ocrText));

        $this->assertEquals('IDR', $result->currency);
    }

    public function test_parse_qris_empty_text(): void
    {
        $result = $this->parser->parse($this->makeEvidence(''));

        $this->assertEquals(DocumentType::QrisReceipt, $result->documentType);
        $this->assertNull($result->merchantName);
        $this->assertNull($result->amount);
        $this->assertNull($result->walletName);
    }
}
