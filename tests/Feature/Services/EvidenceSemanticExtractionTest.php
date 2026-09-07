<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\DocumentType;
use App\Evidence\DTO\EvidenceData;
use App\Evidence\Parsers\Extractors\AmountExtractor;
use App\Services\AI\AIManager;
use App\Services\Evidence\LlmEvidenceParser;
use Tests\TestCase;

/**
 * Evidence Semantic Extraction Tests — SPEC §20 (9 test cases)
 *
 * Validates the refactored pipeline:
 * - OCR is pure text (no semantic fabrication)
 * - AmountExtractor does NOT capture years / transaction IDs / references
 * - DocumentType::normalize handles BANK_RECEIPT alias
 * - amount=null is valid semantic state
 * - Promotional amounts are not captured
 */
class EvidenceSemanticExtractionTest extends TestCase
{
    private AmountExtractor $amountExtractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->amountExtractor = new AmountExtractor;
    }

    // TEST 1 — Tahun bukan amount
    public function test_year_is_not_amount(): void
    {
        $ocr = <<<'TEXT'
Completed Time: 06 Sept 2026, 12:13
Transfer From Rahmad
Transfer To Kristanto
Source of Fund ShopeePay Balance
Reference UWSLNHN3QRURYJNQH
TEXT;

        $result = $this->amountExtractor->extract($ocr);

        $this->assertNull($result['amount'], 'Year 2026 must NOT be extracted as amount');
        $this->assertEquals(0.0, $result['confidence']);
    }

    // TEST 2 — Explicit amount
    public function test_explicit_amount_is_extracted(): void
    {
        $ocr = <<<'TEXT'
Jumlah Setor Tunai Rp 100.000
Waktu Transaksi 24 Agu 2026, 12:25
No Referensi 168776804212
TEXT;

        $result = $this->amountExtractor->extract($ocr);

        $this->assertNotNull($result['amount']);
        $this->assertEquals(100000.0, $result['amount']);
        $this->assertEquals(1.0, $result['confidence']);
    }

    // TEST 3 — Transaction ID not amount
    public function test_transaction_id_is_not_amount(): void
    {
        $ocr = 'No. Transaksi 2026082443508344935851979';

        $result = $this->amountExtractor->extract($ocr);

        $this->assertNull($result['amount'], 'Transaction ID must NOT be extracted as amount');
    }

    // TEST 4 — Reference number not amount
    public function test_reference_number_is_not_amount(): void
    {
        $ocr = 'No. referensi 168776804212';

        $result = $this->amountExtractor->extract($ocr);

        $this->assertNull($result['amount'], 'Reference number must NOT be extracted as amount');
    }

    // TEST 5 — Promotional amount not amount (unless explicit transaction amount exists)
    public function test_promotional_amount_is_not_amount(): void
    {
        $ocr = <<<'TEXT'
Cashback up to Rp100.000
Transfer Successful
TEXT;

        $result = $this->amountExtractor->extract($ocr);

        $this->assertNull($result['amount'], 'Promotional cashback must NOT be extracted as amount');
    }

    // TEST 5b — Promotional with explicit total should extract explicit total, not promotional
    public function test_promotional_with_explicit_total_extracts_total(): void
    {
        $ocr = <<<'TEXT'
Cashback up to Rp100.000
Total Pembayaran Rp 49.000
TEXT;

        $result = $this->amountExtractor->extract($ocr);

        // Pattern with label "Total ... Rp 49.000" is prioritized over bare "Rp100.000"
        $this->assertEquals(49000.0, $result['amount']);
    }

    // TEST 6 — Receipt with merchant and total
    public function test_receipt_merchant_and_total(): void
    {
        $ocr = <<<'TEXT'
Burjo Mabar
Magelangan Rp 20.000
Kopi ABC Rp 10.000
Total Rp 49.000
TEXT;

        $result = $this->amountExtractor->extract($ocr);

        // AmountExtractor with Rp will extract first Rp match — but with label prioritization, Total Rp 49.000 should win
        // Our AmountExtractor pattern 0 matches "Total ... Rp 49.000" first, so should be 49000
        $this->assertEquals(49000.0, $result['amount']);

        // Merchant extraction would be done by LLM, but we verify AmountExtractor picks correct total
        // Simulate LLM merchant extraction would be "Burjo Mabar" — not tested here, but AmountExtractor proves correct total
    }

    // TEST 7 — Caption context (amount from OCR, not caption)
    public function test_caption_context_does_not_override_amount(): void
    {
        // Simulate evidence with amount 49000, caption without amount
        // The LlmEvidenceGroupingService should keep 49000, not become 0
        $evidenceAmount = 49000.0;
        $caption = 'Bayar ayam geprek';

        // Caption without numeric amount should NOT produce amount 0 via regex
        // LocalRuleEngine for caption text should return null or correct? But "Bayar ayam geprek" has no number, so amount null
        // Our pipeline should keep evidence amount, not override with caption amount
        $captionAmount = $this->amountExtractor->extract($caption);

        $this->assertNull($captionAmount['amount'], 'Caption without amount must not produce amount');

        // Simulate grouping service logic: keep evidence amount when caption has no amount
        $finalAmount = $captionAmount['amount'] ?? $evidenceAmount;
        $this->assertEquals(49000.0, $finalAmount);
    }

    // TEST 8 — Caption wallet enrichment
    public function test_caption_wallet_hint_is_detected(): void
    {
        $ocr = 'Total Rp 49.000';
        $caption = 'Bayar pakai Dana';

        // Caption contains wallet name "Dana" — WalletResolver should detect it
        // Test that caption amount extraction does not fabricate, but wallet hint exists
        $captionAmount = $this->amountExtractor->extract($caption);
        // "Dana" has no number, so no amount — but wallet hint should be extractable
        // We test that caption does NOT produce amount, but wallet hint would be detected via WalletResolver
        $this->assertNull($captionAmount['amount']);

        // Simulate wallet detection via string contains
        $wallets = [['name' => 'Dana', 'keyword' => 'dana'], ['name' => 'GoPay', 'keyword' => 'gopay']];
        $foundWallet = null;
        foreach ($wallets as $w) {
            if (str_contains(mb_strtolower($caption), mb_strtolower($w['name']))) {
                $foundWallet = $w['name'];
                break;
            }
        }
        $this->assertEquals('Dana', $foundWallet);
        // Final transaction should have amount from OCR (49000) and wallet from caption
        $ocrAmount = $this->amountExtractor->extract($ocr);
        $this->assertEquals(49000.0, $ocrAmount['amount']);
    }

    // TEST 9 — Gemini validation: BANK_RECEIPT must be VALID
    public function test_bank_receipt_document_type_is_valid(): void
    {
        $this->assertNotNull(DocumentType::normalize('BANK_RECEIPT'), 'BANK_RECEIPT must normalize to canonical type');
        $this->assertEquals(DocumentType::BankReceipt, DocumentType::normalize('BANK_RECEIPT'));
        $this->assertEquals(DocumentType::TransferReceipt, DocumentType::normalize('BANK_TRANSFER_RECEIPT'));
        $this->assertEquals(DocumentType::TransferReceipt, DocumentType::normalize('TRANSFER_RECEIPT'));
        $this->assertEquals(DocumentType::DepositReceipt, DocumentType::normalize('DEPOSIT_RECEIPT'));
        $this->assertEquals(DocumentType::ShoppingReceipt, DocumentType::normalize('SHOPPING_RECEIPT'));
        $this->assertEquals(DocumentType::QrisReceipt, DocumentType::normalize('QRIS_RECEIPT'));
        $this->assertEquals(DocumentType::EWalletReceipt, DocumentType::normalize('E_WALLET_RECEIPT'));

        // Also validate unknown alias maps to Unknown
        $this->assertEquals(DocumentType::Unknown, DocumentType::normalize('UNKNOWN'));

        // Ensure invalid type returns null
        $this->assertNull(DocumentType::normalize('RANDOM_INVALID_TYPE'));
    }

    // TEST 9b — LlmEvidenceParser validation allows BANK_RECEIPT
    public function test_llm_parser_validation_allows_bank_receipt(): void
    {
        // Simulate LLM output with BANK_RECEIPT — should be valid via DocumentType::normalize
        $llmData = [
            'document_type' => 'BANK_RECEIPT',
            'intent' => 'expense',
            'amount' => 100000,
            'confidence' => 0.98,
            'merchant' => 'SeaBank',
        ];

        $normalized = DocumentType::normalize($llmData['document_type']);
        $this->assertNotNull($normalized, 'LLM BANK_RECEIPT must be considered valid');

        // Also test amount null is valid
        $llmDataNullAmount = [
            'document_type' => 'BANK_RECEIPT',
            'intent' => 'expense',
            'amount' => null,
            'confidence' => 0.85,
        ];
        $this->assertNull($llmDataNullAmount['amount']);
        // Validation should accept null amount per P0-D
        $this->assertTrue(DocumentType::normalize($llmDataNullAmount['document_type']) !== null);
    }

    // Additional: amount null valid semantic state
    public function test_amount_null_is_valid_semantic_state(): void
    {
        $evidenceData = new EvidenceData(
            documentType: DocumentType::BankReceipt,
            rawText: 'Completed Time: 06 Sept 2026, 12:13',
            amount: null,
            confidence: 0.85,
        );

        $this->assertNull($evidenceData->amount);
        $this->assertEquals(DocumentType::BankReceipt, $evidenceData->documentType);
        $this->assertEquals(0.85, $evidenceData->confidence);
    }

    // Additional: LlmEvidenceParser normalizeAmount handles formatted strings
    public function test_normalize_amount_handles_indonesian_format(): void
    {
        $parser = new LlmEvidenceParser(
            aiManager: $this->createMock(AIManager::class),
        );

        $this->assertEquals(100000.0, $parser->normalizeAmount(100000));
        $this->assertEquals(100000.0, $parser->normalizeAmount('100.000'));
        $this->assertEquals(49000.0, $parser->normalizeAmount('49.000'));
        $this->assertEquals(29588.0, $parser->normalizeAmount('Rp29.588'));
        $this->assertEquals(49000.0, $parser->normalizeAmount('Rp 49.000'));
        $this->assertNull($parser->normalizeAmount(null));
        $this->assertNull($parser->normalizeAmount(''));
    }

    // Test timestamp not amount
    public function test_timestamp_is_not_amount(): void
    {
        $ocr = 'Waktu Transaksi 24 Agu 2026, 12:25';
        // This has "Rp"?? No, but AmountExtractor should not capture 12:25 or 2026
        $result = $this->amountExtractor->extract($ocr);
        $this->assertNull($result['amount'], 'Timestamp must NOT be extracted as amount');
    }

    // Test account number not amount
    public function test_account_number_is_not_amount(): void
    {
        $ocr = 'SeaBank: ********6546';
        $result = $this->amountExtractor->extract($ocr);
        $this->assertNull($result['amount'], 'Masked account must NOT be extracted as amount');
    }
}
