<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\MultiTransactionResult;
use App\Enums\EvidenceStatus;
use App\Models\Evidence;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Chat\ChatTransactionOrchestrator;
use App\Services\Evidence\LlmEvidenceGroupingService;
use App\Services\Wallet\WalletResolutionService;
use Database\Factories\EvidenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CaptionWalletFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Wallet $danaWallet;

    protected function setUp(): void
    {
        parent::setUp();

        $expenseType = TransactionType::create(['name' => 'Expense']);
        $this->user = User::factory()->create();
        $this->user->wallets()->forceDelete();
        $this->user->categories()->forceDelete();

        $this->danaWallet = $this->user->wallets()->create([
            'name' => 'DANA',
            'keyword' => 'dana',
            'group_type' => 'Liquid',
            'balance' => 100000,
        ]);
        $this->user->wallets()->create([
            'name' => 'Dompet Cash',
            'keyword' => 'cash',
            'group_type' => 'Liquid',
            'balance' => 50000,
        ]);
        $this->user->categories()->create([
            'category_name' => 'Makanan',
            'type_id' => $expenseType->id,
            'keyword' => 'makan, nasi, ayam, rendang, magelangan',
            'icon' => '🍜',
        ]);
        $this->user->categories()->create([
            'category_name' => 'Minuman',
            'type_id' => $expenseType->id,
            'keyword' => 'minum, es, kopi',
            'icon' => '🥤',
        ]);
    }

    private function makeServiceWithCapture(?string &$capturedText): LlmEvidenceGroupingService
    {
        $captured = null;
        $mockOrchestrator = Mockery::mock(ChatTransactionOrchestrator::class);
        $mockOrchestrator->shouldReceive('process')
            ->andReturnUsing(function (User $user, string $text, string $source) use (&$captured, &$capturedText) {
                $captured = $text;
                $capturedText = $text;

                // Return dummy success to avoid hitting real LLM
                return [
                    'success' => true,
                    'is_multi' => true,
                    'multi_result' => new MultiTransactionResult(
                        results: [],
                        provider: 'mock',
                        model: 'mock',
                        confidence: 1.0
                    ),
                ];
            });

        return new LlmEvidenceGroupingService($mockOrchestrator, app(WalletResolutionService::class));
    }

    private function makeEvidence(string $ocrText): Evidence
    {
        return EvidenceFactory::new()->create([
            'user_id' => $this->user->id,
            'ocr_text' => $ocrText,
            'ocr_engine' => 'Tesseract',
            'status' => EvidenceStatus::OcrCompleted,
        ]);
    }

    /** @test */
    public function test_punyaku_plus_dana_both_hints(): void
    {
        $ocr = "Burjo Mabar\nNasi Ayam Bali Crisp 15.000\nMagelangan Rendang 15.000\nKopi ABC 6.000\nTotal Rp 49.000";
        $evidence = $this->makeEvidence($ocr);
        $caption = 'punyaku magelangan rendang dan es kopi abc ya bayar pakai dana';

        $capturedText = null;
        $service = $this->makeServiceWithCapture($capturedText);
        $service->group($evidence, $this->user, $caption);

        $this->assertNotNull($capturedText, 'Captured text should not be null');
        $this->assertStringContainsString('[User filter:', $capturedText, 'Should contain filter hint');
        $this->assertStringContainsString('punyaku magelangan rendang', $capturedText);
        $this->assertStringContainsString('[Wallet hint: DANA]', $capturedText, 'Should contain wallet hint DANA even when punyaku filter present');
    }

    /** @test */
    public function test_punyaku_without_wallet_still_filter(): void
    {
        $ocr = "Burjo Mabar\nTotal Rp 49.000";
        $evidence = $this->makeEvidence($ocr);
        $caption = 'punyaku magelangan rendang aja';

        $capturedText = null;
        $service = $this->makeServiceWithCapture($capturedText);
        $service->group($evidence, $this->user, $caption);

        $this->assertStringContainsString('[User filter:', $capturedText);
        // No wallet hint because caption has no dana keyword
        $this->assertStringNotContainsString('[Wallet hint: DANA]', $capturedText);
    }

    /** @test */
    public function test_wallet_hint_without_filter(): void
    {
        $ocr = "Burjo Mabar\nTotal Rp 49.000";
        $evidence = $this->makeEvidence($ocr);
        $caption = 'bayar pakai dana';

        $capturedText = null;
        $service = $this->makeServiceWithCapture($capturedText);
        $service->group($evidence, $this->user, $caption);

        $this->assertStringContainsString('[Wallet hint: DANA]', $capturedText);
        $this->assertStringNotContainsString('[User filter:', $capturedText);
    }

    /** @test */
    public function test_filter_plus_wallet_dana_flexible_not_hardcoded(): void
    {
        // Create another wallet OVO for same user, test OVO hint also works (flex, not hardcoded DANA)
        $ovo = $this->user->wallets()->create(['name' => 'OVO', 'keyword' => 'ovo', 'group_type' => 'Liquid', 'balance' => 50000]);
        $ocr = "Burjo Mabar\nTotal Rp 49.000";
        $evidence = $this->makeEvidence($ocr);
        $caption = 'punyaku kopi abc bayar pakai ovo';

        $capturedText = null;
        $service = $this->makeServiceWithCapture($capturedText);
        $service->group($evidence, $this->user, $caption);

        $this->assertStringContainsString('[Wallet hint: OVO]', $capturedText, 'Should be flexible for any wallet, not hardcoded DANA');
        $this->assertStringContainsString('[User filter:', $capturedText);
    }

    /** @test */
    public function test_ocr_wallet_detected_when_no_caption_wallet(): void
    {
        // OCR contains wallet keyword, caption has no wallet -> should detect from OCR even if filter present but no wallet hint
        $ocr = "Burjo Mabar\nPayment DANA\nTotal Rp 49.000";
        $evidence = $this->makeEvidence($ocr);
        $caption = 'punyaku magelangan rendang';

        $capturedText = null;
        $service = $this->makeServiceWithCapture($capturedText);
        $service->group($evidence, $this->user, $caption);

        // Because caption wallet is null, OCR wallet DANA should be detected (even with filter)
        $this->assertStringContainsString('[Wallet hint (from OCR): DANA]', $capturedText, 'Should fallback to OCR wallet detection');
    }

    /** @test */
    public function test_punya_ku_spasi_flexible(): void
    {
        // Regression untuk bug "Punya ku" (spasi) vs "punyaku" (rapat) — harus tetap jadi filter
        $ocr = "Burjo Mabar\nNasi Ayam Bali Crisp 15.000\nMagelangan Rendang 15.000\nKopi ABC 6.000\nTotal Rp 49.000";
        $evidence = $this->makeEvidence($ocr);
        $caption = 'Punya ku yang magelangan rendang dan kopi abc ya ku bayar pakai dana';

        $capturedText = null;
        $service = $this->makeServiceWithCapture($capturedText);
        $service->group($evidence, $this->user, $caption);

        $this->assertStringContainsString('[User caption (full):', $capturedText, 'Full caption harus selalu dikirim ke LLM');
        $this->assertStringContainsString('Punya ku yang magelangan rendang', $capturedText);
        $this->assertStringContainsString('[User filter:', $capturedText, 'Punya ku spasi harus tetap trigger filter (normalisasi)');
        $this->assertStringContainsString('[Wallet hint: DANA]', $capturedText, 'Wallet DANA tetap terdeteksi walau filter spasi');
    }

    /** @test */
    public function test_full_caption_always_included(): void
    {
        // Bahkan caption generic tanpa filter/wallet pun harus ada [User caption (full): ...]
        $ocr = "Burjo Mabar\nTotal Rp 49.000";
        $evidence = $this->makeEvidence($ocr);
        $caption = 'tolong catat yang bener ya';

        $capturedText = null;
        $service = $this->makeServiceWithCapture($capturedText);
        $service->group($evidence, $this->user, $caption);

        $this->assertStringContainsString('[User caption (full): "tolong catat yang bener ya"]', $capturedText);
        $this->assertStringContainsString('[User note:', $capturedText);
    }

    /** @test */
    public function test_ocr_wallet_fallback_with_full_caption(): void
    {
        // Caption tanpa wallet + OCR ada DANA → harus fallback ke OCR dengan label baru
        $ocr = "Burjo Mabar\nPayment via DANA\nTotal Rp 49.000";
        $evidence = $this->makeEvidence($ocr);
        $caption = 'cuma magelangan rendang aja';

        $capturedText = null;
        $service = $this->makeServiceWithCapture($capturedText);
        $service->group($evidence, $this->user, $caption);

        $this->assertStringContainsString('[User caption (full):', $capturedText);
        $this->assertStringContainsString('[User filter:', $capturedText);
        $this->assertStringContainsString('[Wallet hint (from OCR): DANA]', $capturedText, 'Harus fallback ke OCR wallet dengan label baru');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
