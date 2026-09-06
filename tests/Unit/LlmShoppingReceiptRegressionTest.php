<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\AiProvider;
use App\Enums\DocumentType;
use App\Evidence\DTO\EvidenceData;
use App\Evidence\Parsers\QrisReceiptParser;
use App\Evidence\Parsers\ShoppingReceiptParser;
use App\Evidence\Parsers\TransferReceiptParser;
use App\Evidence\Pipeline\Context\EvidenceContext;
use App\Evidence\Pipeline\Stages\ParsingStage;
use App\Models\Evidence;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\UserAiCredential;
use App\Models\UserAiPreference;
use App\Services\AI\Adapters\DeepSeekAdapter;
use App\Services\AI\Adapters\GeminiAdapter;
use App\Services\AI\Adapters\OpenAIAdapter;
use App\Services\AI\Adapters\OpenAiCompatibleAdapter;
use App\Services\AI\AiCredentialManager;
use App\Services\AI\AIManager;
use App\Services\AI\AiPreferenceManager;
use App\Services\AI\AiProviderFactory;
use App\Services\Evidence\LlmEvidenceParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LlmShoppingReceiptRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Ensure evidence config
        config(['evidence.llm.enabled' => true]);
        config(['evidence.llm.primary' => false]);
        config(['evidence.llm.fallback_threshold' => 0.6]);
        config(['classifier.engine' => 'RuleBased']);
        $classifierConfig = require base_path('config/classifier.php');
        config(['classifier.keywords' => $classifierConfig['keywords'] ?? []]);

        // Create wallets/categories for user (needed for prompt context)
        $this->user->wallets()->createMany([
            ['name' => 'Dompet Cash', 'keyword' => 'cash', 'group_type' => 'Liquid', 'balance' => 500000],
            ['name' => 'Dana', 'keyword' => 'dana', 'group_type' => 'Liquid', 'balance' => 500000],
        ]);
        $expenseType = TransactionType::firstOrCreate(['name' => 'Expense']);
        $this->user->categories()->createMany([
            ['category_name' => 'Jajan & Nongkrong', 'type_id' => $expenseType->id, 'keyword' => 'jajan'],
            ['category_name' => 'Belanja', 'type_id' => $expenseType->id, 'keyword' => 'belanja'],
            ['category_name' => 'Makan & Minum', 'type_id' => $expenseType->id, 'keyword' => 'makan'],
        ]);

        // Setup AI preference + credential for LLM mock
        UserAiPreference::create([
            'user_id' => $this->user->id,
            'provider' => AiProvider::Gemini->value,
            'selected_model' => 'gemini-2.0-flash',
            'is_active_provider' => true,
        ]);
        UserAiCredential::create([
            'user_id' => $this->user->id,
            'provider' => AiProvider::Gemini->value,
            'api_key' => 'fake-key-123',
            'is_valid' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEvidence(string $ocrText, ?DocumentType $docType = null): Evidence
    {
        return Evidence::factory()->create([
            'user_id' => $this->user->id,
            'ocr_text' => $ocrText,
            'normalized_text' => $ocrText,
            'document_type' => $docType,
        ]);
    }

    private function mockLlmParserWithResponse(string $jsonResponse): LlmEvidenceParser
    {
        $mockGemini = Mockery::mock(GeminiAdapter::class);
        $mockGemini->shouldReceive('generateText')->andReturn($jsonResponse);
        $mockOpenAI = Mockery::mock(OpenAIAdapter::class);
        $mockDeepSeek = Mockery::mock(DeepSeekAdapter::class);
        $mockCompat = Mockery::mock(OpenAiCompatibleAdapter::class);

        $factory = new AiProviderFactory($mockGemini, $mockOpenAI, $mockDeepSeek, $mockCompat);

        // Use real managers (they will read DB preference/credential)
        $realPref = app(AiPreferenceManager::class);
        $realCred = app(AiCredentialManager::class);
        $aiManager = app(AIManager::class);

        return new LlmEvidenceParser($aiManager, $realPref, $realCred, $factory);
    }

    /** @test */
    public function test_burjo_mabar_amount_49000_not_5029(): void
    {
        $ocr = "Burjo Mabar TART A\nJ1. Kesatrian N0. K7, Jab nga\nh, Semarang, Jawa Tengah, 5029\n081225223086\n05 Sep 2026 19:33\nReceiptNumber419408\nOrder ID DSA004 |\nCollected By Kasir Burjp Ma\nNasi Ayam Bali Crisp\nMagelangan Rendang\n-\nPA 3.00\nAis...\n49,000";

        $json = json_encode([
            'document_type' => 'SHOPPING_RECEIPT',
            'intent' => 'expense',
            'amount' => 49000,
            'merchant' => 'Burjo Mabar',
            'category' => 'Jajan & Nongkrong',
            'source_wallet' => null,
            'destination_wallet' => null,
            'reference' => null,
            'confidence' => 0.95,
        ]);

        $evidence = $this->makeEvidence($ocr, DocumentType::ShoppingReceipt);
        $parser = $this->mockLlmParserWithResponse($json);

        $result = $parser->parseShoppingReceipt($evidence, $ocr);

        $this->assertNotNull($result, 'LLM shopping parser should return data');
        $this->assertEquals(49000.0, $result->amount, 'Amount must be 49000, not 5029 kode pos');
        $this->assertNotEquals(5029.0, $result->amount);
        $this->assertEquals(DocumentType::ShoppingReceipt, $result->documentType);
        $this->assertEquals('EXPENSE', $result->transactionType);
        $this->assertEquals('Burjo Mabar', $result->merchantName);
        // Ensure distractor numbers are not chosen
        $this->assertNotEquals(81225223086, $result->amount);
        $this->assertNotEquals(419408, $result->amount);
    }

    /** @test */
    public function test_shopee_ugreen_amount_29588_not_kode_pos(): void
    {
        $ocr = "Nota Pesanan\nNama Pembeli: Umi Mutoharoh Nama Penjual: UGREEN Official Store\nAlamat Pembeli:\nJalan Poros bunga jaya, KA8. OGAN KOMERING ULU TIMUR, MADANG SUKU I, SUMATERA\nSELATAN, ID, 32362\nN0. Handphone Pembeli: 6285700991499\nN0. Pesanan Tanggal Transaksi Metode Pembayaran Jasa Kirim\n260714NGR75UK7 18/07/2026 Cash on Delivery Hemat Kargo\nRincian Pesanan\nN0. Produk Variasi Harga Produk Kuantitas Subtotal\nUGREEN Adapter OTG USB 3.1 Type C To\n1 USB 3.0 Female 50283 50283 Rp39.100 1 Rp39.100\nSubtotal Rp39.100\nTotal Kuantitas (Aktif) 1 produk\nSubtotal Pesanan Rp39.100\nBiaya Layanan Rp2.000\nDiskon Voucher Toko -Rp4.614\nDiskon Voucher Shopee -Rp6.898\nTotal Pembayaran Rp29.588\nBiaya-biaya yang ditagihkan oleh Shopee (jika\nada) sudah termasuk PPN\nPT Shopee International Indonesia";

        $json = json_encode([
            'document_type' => 'SHOPPING_RECEIPT',
            'intent' => 'expense',
            'amount' => 29588,
            'merchant' => 'UGREEN Official Store',
            'category' => 'Belanja',
            'source_wallet' => null,
            'destination_wallet' => null,
            'reference' => '260714NGR75UK7',
            'confidence' => 0.96,
        ]);

        $evidence = $this->makeEvidence($ocr, DocumentType::ShoppingReceipt);
        $parser = $this->mockLlmParserWithResponse($json);

        $result = $parser->parseShoppingReceipt($evidence, $ocr);

        $this->assertNotNull($result);
        $this->assertEquals(29588.0, $result->amount, 'Total Pembayaran 29588, bukan kode pos 32362 atau harga item 39100');
        $this->assertNotEquals(32362.0, $result->amount);
        $this->assertNotEquals(39100.0, $result->amount);
        $this->assertNotEquals(50283.0, $result->amount);
        $this->assertEquals('UGREEN Official Store', $result->merchantName);
    }

    /** @test */
    public function test_amount_normalization_49000_variants(): void
    {
        // Test normalizeAmount handles various formats directly (without LLM call)
        $parser = app(LlmEvidenceParser::class);

        $this->assertEquals(49000.0, $parser->normalizeAmount('49,000'));
        $this->assertEquals(29588.0, $parser->normalizeAmount('Rp29.588'));
        $this->assertEquals(29588.0, $parser->normalizeAmount('29.588'));
        $this->assertEquals(39100.0, $parser->normalizeAmount('39.100'));
        $this->assertEquals(49000.0, $parser->normalizeAmount(49000));
        $this->assertEquals(49000.0, $parser->normalizeAmount(49000.0));
        $this->assertEquals(29588.0, $parser->normalizeAmount('Rp 29.588'));
    }

    /** @test */
    public function test_invalid_llm_response_returns_null_and_allows_fallback(): void
    {
        $ocr = "Burjo Mabar\nSemarang, Jawa Tengah, 5029\n49,000";
        $evidence = $this->makeEvidence($ocr, DocumentType::ShoppingReceipt);

        // Case 1: amount 0 invalid
        $jsonZero = json_encode([
            'document_type' => 'SHOPPING_RECEIPT',
            'intent' => 'expense',
            'amount' => 0,
            'merchant' => 'Burjo',
            'category' => 'Jajan',
            'source_wallet' => null,
            'destination_wallet' => null,
            'reference' => null,
            'confidence' => 0.9,
        ]);
        $parserZero = $this->mockLlmParserWithResponse($jsonZero);
        $resultZero = $parserZero->parseShoppingReceipt($evidence, $ocr);
        $this->assertNull($resultZero, 'amount 0 should be invalid -> null -> fallback');

        // Case 2: missing amount field
        $jsonMissing = json_encode([
            'document_type' => 'SHOPPING_RECEIPT',
            'intent' => 'expense',
            'merchant' => 'Burjo',
            'confidence' => 0.9,
        ]);
        $parserMissing = $this->mockLlmParserWithResponse($jsonMissing);
        $resultMissing = $parserMissing->parseShoppingReceipt($evidence, $ocr);
        $this->assertNull($resultMissing, 'missing amount should be invalid');

        // Case 3: not json
        $parserBad = $this->mockLlmParserWithResponse('not a json at all');
        $resultBad = $parserBad->parseShoppingReceipt($evidence, $ocr);
        $this->assertNull($resultBad, 'invalid json should return null');
    }

    /** @test */
    public function test_shopping_receipt_disallows_income_intent(): void
    {
        $ocr = "Burjo Mabar\n49,000";
        $jsonIncome = json_encode([
            'document_type' => 'SHOPPING_RECEIPT',
            'intent' => 'income',
            'amount' => 49000,
            'merchant' => 'Burjo',
            'category' => 'Jajan',
            'source_wallet' => null,
            'destination_wallet' => null,
            'reference' => null,
            'confidence' => 0.9,
        ]);
        $evidence = $this->makeEvidence($ocr, DocumentType::ShoppingReceipt);
        $parser = $this->mockLlmParserWithResponse($jsonIncome);
        $result = $parser->parseShoppingReceipt($evidence, $ocr);
        $this->assertNotNull($result);
        $this->assertEquals('EXPENSE', $result->transactionType, 'shopping income must be forced to expense');
    }

    /** @test */
    public function test_parsing_stage_uses_llm_for_shopping_receipt_primary(): void
    {
        $ocr = "Burjo Mabar TART A\nJalan Test 5029\n49,000";
        $evidence = $this->makeEvidence($ocr, DocumentType::ShoppingReceipt);
        // Mock LLm parser to return correct 49000
        $mockLlm = Mockery::mock(LlmEvidenceParser::class);
        $expectedData = new EvidenceData(
            documentType: DocumentType::ShoppingReceipt,
            rawText: $ocr,
            merchantName: 'Burjo Mabar',
            transactionType: 'EXPENSE',
            amount: 49000.0,
            currency: 'IDR',
            confidence: 0.95,
            metadata: ['engine' => 'LLM_SHOPPING_SEMANTIC'],
        );
        $mockLlm->shouldReceive('parseShoppingReceipt')->once()->andReturn($expectedData);

        $stage = new ParsingStage(
            new TransferReceiptParser,
            new ShoppingReceiptParser,
            new QrisReceiptParser,
            $mockLlm
        );

        $context = new EvidenceContext($evidence);
        $context->ocrText = $ocr;
        $context->normalizedText = $ocr;
        $context->documentType = DocumentType::ShoppingReceipt;

        $stage->handle($context, function ($ctx) {});

        $this->assertNotNull($context->parsedData);
        $this->assertEquals(49000.0, $context->parsedData->amount);
        $this->assertEquals('LLM_SHOPPING_SEMANTIC', $context->metadata['parser_engine']);
    }

    /** @test */
    public function test_parsing_stage_fallback_to_regex_when_llm_invalid(): void
    {
        $ocr = "INDOMARET\nTOTAL\n25.300\nTUNAI\n30.000";
        $evidence = $this->makeEvidence($ocr, DocumentType::ShoppingReceipt);

        $mockLlm = Mockery::mock(LlmEvidenceParser::class);
        $mockLlm->shouldReceive('parseShoppingReceipt')->once()->andReturn(null); // LLM invalid -> fallback

        $stage = new ParsingStage(
            new TransferReceiptParser,
            new ShoppingReceiptParser,
            new QrisReceiptParser,
            $mockLlm
        );

        $context = new EvidenceContext($evidence);
        $context->ocrText = $ocr;
        $context->normalizedText = $ocr;
        $context->documentType = DocumentType::ShoppingReceipt;

        $stage->handle($context, function ($ctx) {});

        $this->assertNotNull($context->parsedData);
        $this->assertEquals(25300.0, $context->parsedData->amount, 'fallback regex should produce 25300');
        $this->assertStringContainsString('fallback', strtolower($context->metadata['parser_engine'] ?? ''));
    }

    /** @test */
    public function test_transfer_receipt_still_uses_transfer_parser_not_shopping_llm(): void
    {
        $ocr = "Transfer berhasil\nRp500.000 ke BCA 1234567890\nRef: TXN20260715001";
        $evidence = $this->makeEvidence($ocr, DocumentType::TransferReceipt);

        // Ensure Llm parser is NOT called for transfer when primary false and confidence high
        $mockLlm = Mockery::mock(LlmEvidenceParser::class);
        // For transfer high confidence, fallback shouldn't trigger; but allow zero calls or fallback check
        $mockLlm->shouldReceive('parse')->zeroOrMoreTimes()->andReturn(null);

        $stage = new ParsingStage(
            new TransferReceiptParser,
            new ShoppingReceiptParser,
            new QrisReceiptParser,
            $mockLlm
        );

        $context = new EvidenceContext($evidence);
        $context->ocrText = $ocr;
        $context->normalizedText = $ocr;
        $context->documentType = DocumentType::TransferReceipt;

        $stage->handle($context, function ($ctx) {});

        $this->assertNotNull($context->parsedData);
        // Transfer parser should extract 500000; ensure not null
        $this->assertEquals(500000.0, $context->parsedData->amount);
        $this->assertEquals(DocumentType::TransferReceipt, $context->parsedData->documentType);
    }

    /** @test */
    public function test_distractor_numbers_not_selected_as_amount(): void
    {
        // Direct validation via LlmEvidenceParser decode path with distractor-heavy JSON
        $ocr = 'Jl. Kesatrian 5029 HP 081225223086 ReceiptNumber419408 Order DSA004 QTY 3.00 Subtotal Rp10.000 Total Pembayaran Rp49.000';
        $json = json_encode([
            'document_type' => 'SHOPPING_RECEIPT',
            'intent' => 'expense',
            'amount' => '49,000', // string with comma
            'merchant' => 'Burjo Mabar',
            'category' => 'Jajan',
            'source_wallet' => null,
            'destination_wallet' => null,
            'reference' => 'DSA004',
            'confidence' => 0.92,
        ]);
        $evidence = $this->makeEvidence($ocr, DocumentType::ShoppingReceipt);
        $parser = $this->mockLlmParserWithResponse($json);
        $result = $parser->parseShoppingReceipt($evidence, $ocr);
        $this->assertEquals(49000.0, $result->amount);
        // Ensure string "49,000" normalized correctly, not misinterpreted
        $this->assertNotEquals(5029.0, $result->amount);
        $this->assertNotEquals(81225223086.0, $result->amount);
        $this->assertNotEquals(419408.0, $result->amount);
    }
}
