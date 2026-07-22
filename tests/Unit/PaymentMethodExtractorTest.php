<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Evidence\Parsers\Extractors\PaymentMethodExtractor;
use Tests\TestCase;

class PaymentMethodExtractorTest extends TestCase
{
    private PaymentMethodExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('shopping_parser.payment_methods', [
            'Tunai' => ['tunai', 'cash', 'CASH', 'TUNAI'],
            'Debit' => ['debit', 'DEBIT', 'kartu debit'],
            'Kredit' => ['kredit', 'CREDIT', 'kartu kredit', 'credit card'],
            'QRIS' => ['qris', 'QRIS', 'QR Code'],
            'GoPay' => ['gopay', 'GOPAY', 'go pay'],
            'OVO' => ['ovo', 'OVO'],
            'DANA' => ['dana', 'DANA'],
            'ShopeePay' => ['shopeepay', 'SHOPEEPAY', 'shopee pay'],
            'LinkAja' => ['linkaja', 'LINKAJA', 'link aja'],
            'Transfer' => ['transfer', 'TRANSFER', 'tf bank'],
        ]);
        $this->extractor = new PaymentMethodExtractor;
    }

    public function test_extract_cash(): void
    {
        $result = $this->extractor->extract("BAYAR\nTUNAI\n25.000\nKEMBALIAN\n2.000");
        $this->assertEquals('Tunai', $result['payment_method']);
        $this->assertGreaterThanOrEqual(0.8, $result['confidence']);
    }

    public function test_extract_debit(): void
    {
        $result = $this->extractor->extract("BAYAR\nDEBIT\n25.000");
        $this->assertEquals('Debit', $result['payment_method']);
    }

    public function test_extract_qris(): void
    {
        $result = $this->extractor->extract("BAYAR\nQRIS\n25.000");
        $this->assertEquals('QRIS', $result['payment_method']);
    }

    public function test_extract_gopay(): void
    {
        $result = $this->extractor->extract("BAYAR\nGOPAY\n25.000");
        $this->assertEquals('GoPay', $result['payment_method']);
    }

    public function test_extract_ovo(): void
    {
        $result = $this->extractor->extract("BAYAR\nOVO\n25.000");
        $this->assertEquals('OVO', $result['payment_method']);
    }

    public function test_extract_transfer(): void
    {
        $result = $this->extractor->extract("BAYAR\nTRANSFER BCA\n25.000");
        $this->assertEquals('Transfer', $result['payment_method']);
    }

    public function test_extract_empty(): void
    {
        $result = $this->extractor->extract('');
        $this->assertNull($result['payment_method']);
        $this->assertEquals(0.0, $result['confidence']);
    }
}
