<?php

declare(strict_types=1);

namespace Tests\Feature\Chat;

use Tests\TestCase;
use App\Chat\Formatters\TelegramFormatter;
use App\Chat\Formatters\WebFormatter;
use App\Chat\DTOs\ChatResponse;
use App\Chat\DTOs\ChatContext;
use App\Chat\Components\TextComponent;
use App\Chat\Components\DividerComponent;
use App\Chat\Components\TransactionCardComponent;
use App\Chat\Components\SummaryCardComponent;
use App\Chat\Components\ErrorComponent;
use App\Chat\Components\WarningComponent;
use App\Chat\Components\SuggestionComponent;
use App\Chat\Errors\ErrorDetail;
use App\Enums\ChatIntent;
use App\Enums\ChatPlatform;
use App\Enums\ChatErrorSeverity;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\Category;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature test untuk TelegramFormatter dan WebFormatter.
 *
 * Coverage:
 *  1. Single transaction sukses → format kartu Telegram dengan MoneyFormatter::rupiah()
 *  2. Multi-transaction semua sukses → SummaryCard + list item
 *  3. Multi-transaction partial (ada yang gagal) → item sukses + ErrorComponent
 *  4. Multi-transaction semua gagal → hanya error
 *  5. Error message AI tidak dikonfigurasi
 *  6. Error message wallet tidak ditemukan
 *  7. Error message kategori tidak ditemukan
 *  8. Error message AI timeout
 *  9. Error message rate limit
 * 10. WebFormatter → structured array untuk Vue
 * 11. WarningComponent & SuggestionComponent render
 */
class FormatterTest extends TestCase
{
    use RefreshDatabase;

    private TelegramFormatter $telegramFormatter;
    private WebFormatter      $webFormatter;
    private ChatContext       $context;
    private User              $user;
    private TransactionType   $expenseType;
    private Category          $category;
    private Wallet            $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->telegramFormatter = new TelegramFormatter();
        $this->webFormatter      = new WebFormatter();

        $this->context = ChatContext::make(
            platform:       ChatPlatform::Telegram,
            conversationId: '123456789',
            locale:         'id',
        );

        $this->user        = User::factory()->create(['name' => 'Budi']);
        $this->expenseType = TransactionType::create(['name' => 'Expense']);
        $this->category    = $this->user->categories()->create([
            'category_name' => 'Makan & Minum',
            'keyword'       => 'makan',
            'type_id'       => $this->expenseType->id,
        ]);
        $this->wallet = $this->user->wallets()->create([
            'name'       => 'Cash',
            'keyword'    => 'cash',
            'balance'    => 100000.00,
            'group_type' => 'Liquid',
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────

    /**
     * Buat TransactionLog minimal untuk dipakai di komponen.
     */
    private function makeTransaction(float $amount = 15000.00, bool $isCleared = true): TransactionLog
    {
        return TransactionLog::create([
            'reference_number'     => 'TEST-' . uniqid(),
            'user_id'              => $this->user->id,
            'date'                 => now()->format('Y-m-d'),
            'type_id'              => $this->expenseType->id,
            'category_id'          => $this->category->id,
            'source_wallet_id'     => $this->wallet->id,
            'destination_wallet_id'=> null,
            'amount'               => $amount,
            'balance_before'       => 100000.00,
            'balance_after'        => 100000.00 - $amount,
            'subject'              => $this->user->name,
            'notes'                => 'test transaksi',
            'is_cleared'           => $isCleared,
        ])->load(['category', 'sourceWallet', 'destinationWallet', 'type']);
    }

    // ══════════════════════════════════════════════════════════════════
    // TELEGRAM FORMATTER — Single Transaction
    // ══════════════════════════════════════════════════════════════════

    /** @test */
    public function test_telegram_renders_single_transaction_with_rupiah_format(): void
    {
        $trx      = $this->makeTransaction(15000.00);
        $response = ChatResponse::singleSuccess([
            new TransactionCardComponent(transaction: $trx, showDetails: true),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        // Harus memuat format Rupiah dari MoneyFormatter::rupiah()
        $this->assertStringContainsString('Rp 15.000', $output);
        // Harus tidak ada format lama (tanpa spasi setelah Rp)
        $this->assertStringNotContainsString('Rp15.000', $output);
    }

    /** @test */
    public function test_telegram_renders_single_transaction_card_with_correct_fields(): void
    {
        $trx      = $this->makeTransaction(50000.00);
        $response = ChatResponse::singleSuccess([
            new TransactionCardComponent(transaction: $trx, showDetails: true),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertStringContainsString('Rp 50.000', $output);
        $this->assertStringContainsString('Cash', $output);         // wallet name
        $this->assertStringContainsString('Makan & Minum', $output); // category name
    }

    /** @test */
    public function test_telegram_renders_draft_transaction_as_uncleared(): void
    {
        $trx      = $this->makeTransaction(25000.00, isCleared: false);
        $response = ChatResponse::draft([
            new TransactionCardComponent(transaction: $trx, showDetails: true),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertStringContainsString('Rp 25.000', $output);
        $this->assertTrue($response->intent === ChatIntent::Draft);
    }

    /** @test */
    public function test_telegram_single_compact_card_renders_amount_for_multi_list(): void
    {
        $trx      = $this->makeTransaction(20000.00);
        $response = ChatResponse::singleSuccess([
            new TransactionCardComponent(transaction: $trx, index: 1, showDetails: false),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        // Compact mode (untuk list multi-tx): format rupiah tetap ada
        $this->assertStringContainsString('Rp 20.000', $output);
        $this->assertStringContainsString('1.', $output);            // index
    }

    // ══════════════════════════════════════════════════════════════════
    // TELEGRAM FORMATTER — Multi Transaction
    // ══════════════════════════════════════════════════════════════════

    /** @test */
    public function test_telegram_renders_multi_transaction_all_success_summary(): void
    {
        $trx1 = $this->makeTransaction(15000.00);
        $trx2 = $this->makeTransaction(30000.00);

        $response = ChatResponse::multiResult(hasAnySuccess: true, components: [
            new SummaryCardComponent(total: 2, success: 2, failed: 0, confidence: 0.95),
            new DividerComponent(),
            new TransactionCardComponent(transaction: $trx1, index: 1, showDetails: false),
            new TransactionCardComponent(transaction: $trx2, index: 2, showDetails: false),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        // Summary card teks
        $this->assertStringContainsString('2', $output);
        // Kedua nominal tampil
        $this->assertStringContainsString('Rp 15.000', $output);
        $this->assertStringContainsString('Rp 30.000', $output);
        // Intent multi
        $this->assertSame(ChatIntent::MultiTransaction, $response->intent);
    }

    /** @test */
    public function test_telegram_renders_multi_transaction_partial_with_error_item(): void
    {
        $trx1 = $this->makeTransaction(20000.00);

        $response = ChatResponse::multiResult(hasAnySuccess: true, components: [
            new SummaryCardComponent(total: 2, success: 1, failed: 1, confidence: 0.90),
            new DividerComponent(),
            new TransactionCardComponent(transaction: $trx1, index: 1, showDetails: false),
            new ErrorComponent(
                messageKey: 'chat.wallet.not_found',
                params:     ['name' => 'spay'],
                raw:        'kopi 15k spay',
                index:      2,
                severity:   ChatErrorSeverity::Error,
                recoverable: true,
            ),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        // Item sukses tampil
        $this->assertStringContainsString('Rp 20.000', $output);
        // Item error tampil (index 2 + ❌)
        $this->assertStringContainsString('2.', $output);
        $this->assertStringContainsString('❌', $output);
        // Raw text ditampilkan
        $this->assertStringContainsString('kopi 15k spay', $output);
    }

    /** @test */
    public function test_telegram_renders_multi_transaction_all_failed_summary(): void
    {
        $response = ChatResponse::multiResult(hasAnySuccess: false, components: [
            new SummaryCardComponent(total: 2, success: 0, failed: 2, confidence: 0.50),
            new DividerComponent(),
            new ErrorComponent(
                messageKey:  'chat.wallet.not_found',
                params:      ['name' => 'dana'],
                raw:         'makan 20k dana',
                index:       1,
                severity:    ChatErrorSeverity::Error,
                recoverable: true,
            ),
            new ErrorComponent(
                messageKey:  'chat.wallet.not_found',
                params:      ['name' => 'ovo'],
                raw:         'bensin 50k ovo',
                index:       2,
                severity:    ChatErrorSeverity::Error,
                recoverable: true,
            ),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertFalse($response->success);
        $this->assertStringContainsString('❌', $output);
        // Kedua raw text muncul
        $this->assertStringContainsString('makan 20k dana', $output);
        $this->assertStringContainsString('bensin 50k ovo', $output);
    }

    // ══════════════════════════════════════════════════════════════════
    // TELEGRAM FORMATTER — Error Messages
    // ══════════════════════════════════════════════════════════════════

    /** @test */
    public function test_telegram_renders_ai_not_configured_error(): void
    {
        $response = ChatResponse::failure([
            ErrorDetail::aiNotConfigured(),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
        $this->assertSame(ChatIntent::Error, $response->intent);
        $this->assertFalse($response->success);
    }

    /** @test */
    public function test_telegram_renders_ai_timeout_error(): void
    {
        $response = ChatResponse::failure([
            ErrorDetail::aiTimeout('Gemini'),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
        $this->assertFalse($response->success);
    }

    /** @test */
    public function test_telegram_renders_ai_rate_limit_error(): void
    {
        $response = ChatResponse::failure([
            ErrorDetail::aiRateLimit('OpenAI'),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }

    /** @test */
    public function test_telegram_renders_wallet_not_found_error(): void
    {
        $response = ChatResponse::failure([
            ErrorDetail::walletNotFound('spay'),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }

    /** @test */
    public function test_telegram_renders_category_not_found_error(): void
    {
        $response = ChatResponse::failure([
            ErrorDetail::categoryNotFound('groceries'),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }

    /** @test */
    public function test_telegram_renders_system_error(): void
    {
        $response = ChatResponse::failure([
            ErrorDetail::systemError(),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
        $this->assertFalse($response->success);
    }

    /** @test */
    public function test_telegram_renders_ai_provider_error(): void
    {
        $response = ChatResponse::failure([
            ErrorDetail::aiProviderError('DeepSeek', 'Internal Server Error 500'),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }

    /** @test */
    public function test_telegram_renders_warning_component(): void
    {
        $response = ChatResponse::singleSuccess([
            new WarningComponent(
                messageKey: 'chat.warning.low_confidence',
                params:     [],
            ),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertStringContainsString('⚠️', $output);
    }

    /** @test */
    public function test_telegram_renders_suggestion_component(): void
    {
        $response = ChatResponse::singleSuccess([
            new SuggestionComponent(
                messageKey: 'chat.suggestion.add_wallet',
                params:     [],
            ),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertStringContainsString('💡', $output);
    }

    /** @test */
    public function test_telegram_renders_divider_component(): void
    {
        $response = ChatResponse::singleSuccess([
            new DividerComponent(),
        ]);

        $output = $this->telegramFormatter->format($response, $this->context);

        $this->assertStringContainsString('─', $output);
    }

    // ══════════════════════════════════════════════════════════════════
    // WEB FORMATTER — Structured Output
    // ══════════════════════════════════════════════════════════════════

    /** @test */
    public function test_web_formatter_returns_structured_array(): void
    {
        $trx      = $this->makeTransaction(50000.00);
        $response = ChatResponse::singleSuccess([
            new TransactionCardComponent(transaction: $trx, showDetails: true),
        ]);

        $webContext = ChatContext::make(
            platform:       ChatPlatform::Web,
            conversationId: 'web-session-123',
            locale:         'id',
        );

        $output = $this->webFormatter->format($response, $webContext);

        $this->assertIsArray($output);
        $this->assertArrayHasKey('components', $output);
        $this->assertArrayHasKey('errors', $output);
        $this->assertArrayHasKey('metadata', $output);
    }

    /** @test */
    public function test_web_formatter_transaction_card_has_float_amount(): void
    {
        $trx      = $this->makeTransaction(50000.00);
        $response = ChatResponse::singleSuccess([
            new TransactionCardComponent(transaction: $trx, showDetails: true),
        ]);

        $webContext = ChatContext::make(
            platform:       ChatPlatform::Web,
            conversationId: 'web-session-123',
            locale:         'id',
        );

        $output     = $this->webFormatter->format($response, $webContext);
        $txCard     = $output['components'][0];

        $this->assertSame('transaction_card', $txCard['type']);
        // amount harus float (bukan string) karena cast di model
        $this->assertIsFloat($txCard['transaction']['amount']);
        $this->assertSame(50000.0, $txCard['transaction']['amount']);
        // amount_formatted harus menggunakan MoneyFormatter::rupiah()
        $this->assertSame('Rp 50.000', $txCard['transaction']['amount_formatted']);
    }

    /** @test */
    public function test_web_formatter_renders_multi_transaction_components(): void
    {
        $trx = $this->makeTransaction(30000.00);

        $webContext = ChatContext::make(
            platform:       ChatPlatform::Web,
            conversationId: 'web-session-456',
            locale:         'id',
        );

        $response = ChatResponse::multiResult(hasAnySuccess: true, components: [
            new SummaryCardComponent(total: 2, success: 1, failed: 1, confidence: 0.88),
            new TransactionCardComponent(transaction: $trx, index: 1, showDetails: false),
            new ErrorComponent(
                messageKey:  'chat.category.not_found',
                params:      ['name' => 'groceries'],
                raw:         'belanja groceries 50k',
                index:       2,
                severity:    ChatErrorSeverity::Error,
                recoverable: true,
            ),
        ]);

        $output     = $this->webFormatter->format($response, $webContext);
        $components = $output['components'];

        $this->assertCount(3, $components);
        $this->assertSame('summary_card', $components[0]['type']);
        $this->assertSame('transaction_card', $components[1]['type']);
        $this->assertSame('error', $components[2]['type']);

        // Summary card counts
        $this->assertSame(2, $components[0]['total']);
        $this->assertSame(1, $components[0]['success']);
        $this->assertSame(1, $components[0]['failed']);

        // Error component
        $this->assertSame(2, $components[2]['index']);
        $this->assertSame('belanja groceries 50k', $components[2]['raw']);
    }

    /** @test */
    public function test_web_formatter_renders_error_response_for_failure(): void
    {
        $webContext = ChatContext::make(
            platform:       ChatPlatform::Web,
            conversationId: 'web-session-789',
            locale:         'id',
        );

        $response = ChatResponse::failure([
            ErrorDetail::aiNotConfigured(),
        ]);

        $output = $this->webFormatter->format($response, $webContext);

        $this->assertIsArray($output);
        $this->assertNotEmpty($output['components']);
        $this->assertSame('error', $output['components'][0]['type']);
        $this->assertSame(ChatErrorSeverity::Critical->value, $output['components'][0]['severity']);
    }
}
