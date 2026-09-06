<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Evidence\Parsers\Extractors\NumberParser;
use App\Evidence\Parsers\Extractors\SummaryExtractor;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\AI\LocalRuleEngine;
use App\Services\AI\Memory\KeywordResolverService;
use App\Services\AI\Memory\MemoryDecayEngine;
use App\Services\Category\CategoryResolutionService;
use App\Services\Wallet\WalletResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LreAmountOptimalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private LocalRuleEngine $lre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lre = new LocalRuleEngine(
            new CategoryResolutionService,
            new WalletResolutionService,
            new KeywordResolverService(new MemoryDecayEngine),
        );

        // Need transaction types and user categories/wallets for category resolution
        $incomeType = TransactionType::create(['name' => 'Income']);
        $expenseType = TransactionType::create(['name' => 'Expense']);
        TransactionType::create(['name' => 'Transfer']);
        TransactionType::create(['name' => 'Debt']);
        TransactionType::create(['name' => 'Receivable']);

        $this->user = User::factory()->create();
        $this->user->wallets()->forceDelete();
        $this->user->categories()->forceDelete();
        $this->user->wallets()->createMany([
            ['name' => 'DANA', 'keyword' => 'dana', 'group_type' => 'Liquid', 'balance' => 100000],
            ['name' => 'Dompet Cash', 'keyword' => 'cash', 'group_type' => 'Liquid', 'balance' => 100000],
        ]);
        $this->user->categories()->createMany([
            ['category_name' => 'Makanan', 'type_id' => $expenseType->id, 'keyword' => 'makan, nasi, ayam, rendang, magelangan', 'icon' => '🍜'],
            ['category_name' => 'Minuman', 'type_id' => $expenseType->id, 'keyword' => 'minum, es, kopi, nutrisari, energen', 'icon' => '🥤'],
            ['category_name' => 'Belanja', 'type_id' => $expenseType->id, 'keyword' => 'belanja, pembayaran', 'icon' => '🛒'],
        ]);
    }

    private function extractAmount(string $text): ?float
    {
        $ref = new \ReflectionMethod(LocalRuleEngine::class, 'extractAmount');
        $ref->setAccessible(true);
        $res = $ref->invoke($this->lre, $text);

        return $res['amount'] ?? null;
    }

    /** @test */
    public function test_chat_20_ribu_first_match(): void
    {
        // Chat biasa harus tetap first-match 20k -> 20000
        $amount = $this->extractAmount('bayar 20 ribu untuk makan');
        $this->assertEquals(20000.0, $amount);
    }

    /** @test */
    public function test_shopee_total_29588_not_32362(): void
    {
        // Shopee invoice: kode pos 32362 muncul sebelum Total Pembayaran 29.588
        // LRE harus pilih Total Pembayaran, bukan 32362
        $text = "Nota Pesanan\nNama Pembeli: Umi Mutoharoh\nAlamat: Jalan Poros bunga jaya, KA8. OGAN KOMERING ULU TIMUR, ID, 32362\nNo. Handphone: 6285700991499\nNo Pesanan 260714NGR75UK7 18/07/2026 Cash on Delivery\nRincian Pesanan\nSubtotal Rp39.100\nBiaya Layanan Rp2.000\nDiskon Voucher -Rp4.614\nDiskon Voucher Shopee -Rp6.898\nTotal Pembayaran Rp29.588\nPT Shopee International Indonesia";
        $amount = $this->extractAmount($text);
        $this->assertEquals(29588.0, $amount, 'Should pick Total Pembayaran 29.588 not kode pos 32362');
    }

    /** @test */
    public function test_gofood_service_fee_5450_not_850(): void
    {
        // GoFood hasil OCR: header garbled 850 sebelum Service Fee 5.450
        $text = "00.50 MW # BO Fi ull 850\n< Order Details a @\nItem Details\n1x Xtra Large Paha Atas Rp26.950\nItem Subtotal (1 item) Rp26.950\nFood Discount Voucher -Rp2.000\nDelivery Fee @ Rp-600 Rp0\nService Fee @ Rp500\nRp5.450\nTax included\nOrderID3233791448024576571COPY\nOrder Time 26 Aug 2026 17:22\nPayment SeaBank Bayar Instan";
        $amount = $this->extractAmount($text);
        // Evidence-like text should pick last Rp (5.450) not first 850
        $this->assertEquals(5450.0, $amount, 'Should pick Rp5.450 not 850 from garbled header');
    }

    /** @test */
    public function test_burjo_49000(): void
    {
        // Burjo Mabar foto: 6 items + Subtotal/Total Rp 49.000
        $text = "Burjo Mabar\nJl. Kesatrian No. K7, Jatingaleh Semarang 50254\nCollected By Kasir Burjo Mabar\nNasi Ayam Bali Crisp 1x @15.000 15.000\nMagelangan Rendang 1x @15.000 15.000\nEnergen 1x @6.000 6.000\nNutrisari 1x @5.000 5.000\nAir Es 1x @2.000 2.000\nKopi ABC 1x @6.000 6.000\nSubtotal Rp 49.000\nTotal Rp 49.000\nOther Rp 49.000";
        $amount = $this->extractAmount($text);
        $this->assertEquals(49000.0, $amount, 'Burjo total should be 49000');
    }

    /** @test */
    public function test_number_parser_49000_not_5029(): void
    {
        // 49,000 Indonesian format should be 49000, not 5029 (from 5.029 misread)
        $this->assertEquals(49000.0, NumberParser::parse('49,000'));
        $this->assertEquals(49000.0, NumberParser::parse('49.000'));
        // 5.029 with dot as thousand should be 5029 — but if misread from 49.000, that's OCR error, not parser error
        $this->assertEquals(5029.0, NumberParser::parse('5.029'));
    }

    /** @test */
    public function test_summary_extractor_total_pembayaran(): void
    {
        $this->app['config']->set('shopping_parser.summary_line_patterns', [
            '/^(?:grand\s*)?total/i',
            '/^sub\s*total/i',
        ]);
        $extractor = new SummaryExtractor;
        $text = "SHOPEE\nSubtotal Pesanan Rp39.100\nBiaya Layanan Rp2.000\nTotal Pembayaran Rp29.588\nID, 32362";
        $result = $extractor->extract($text);
        $this->assertEquals(29588.0, $result['total'], 'SummaryExtractor should pick Total Pembayaran as total');
    }

    /** @test */
    public function test_summary_extractor_burjo_49000(): void
    {
        $extractor = new SummaryExtractor;
        $text = "Burjo Mabar\nSubtotal Rp 49.000\nTotal Rp 49.000\nOther Rp 49.000";
        $result = $extractor->extract($text);
        $this->assertEquals(49000.0, $result['total']);
    }

    /** @test */
    public function test_evidence_like_detection_not_break_chat(): void
    {
        // Chat short text should NOT be treated as evidence-like, first-match should apply
        $amount = $this->extractAmount('Iqbal bayar hutang 20 ribu tunai');
        $this->assertEquals(20000.0, $amount);
        // Evidence-like long text with Rp 2x should use Rp priority
        $long = str_repeat('a ', 100)." Subtotal Rp20.000\nTotal Rp30.000";
        $amount2 = $this->extractAmount($long);
        $this->assertEquals(30000.0, $amount2);
    }
}
