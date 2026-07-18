<?php

declare(strict_types=1);

namespace Tests\Feature\Chat;

use Tests\TestCase;
use App\Services\Chat\Formatters\TelegramMultiTransactionFormatter;
use App\DTO\MultiTransactionResult;
use App\DTO\MultiTransactionItem;
use App\Enums\MultiTransactionErrorCode;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\Category;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature test untuk TelegramMultiTransactionFormatter.
 *
 * Formatter ini mengubah MultiTransactionResult menjadi Telegram Markdown string.
 *
 * Coverage:
 *  1. Semua transaksi sukses → header "Berhasil" + list item dengan MoneyFormatter::rupiahCompact()
 *  2. Semua transaksi gagal → header "Gagal" + list error dengan reason
 *  3. Partial (campur sukses + gagal) → header count + mixed list
 *  4. Format nominal memakai titik sebagai separator ribuan (bukan koma)
 *  5. Footer provider AI muncul jika ada sukses
 *  6. Footer provider TIDAK muncul jika semua gagal
 *  7. Error: WALLET_NOT_FOUND — reason tampil
 *  8. Error: CATEGORY_NOT_FOUND — reason tampil
 *  9. Error: INVALID_AMOUNT — reason tampil
 * 10. Urutan item dipertahankan sesuai index
 */
class TelegramMultiTransactionFormatterTest extends TestCase
{
    use RefreshDatabase;

    private TelegramMultiTransactionFormatter $formatter;
    private User           $user;
    private TransactionType $expenseType;
    private Category       $category;
    private Wallet         $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter   = new TelegramMultiTransactionFormatter();
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
            'balance'    => 200000.00,
            'group_type' => 'Liquid',
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────

    private function makeTrx(float $amount): TransactionLog
    {
        return TransactionLog::create([
            'reference_number'      => 'TEST-' . uniqid(),
            'user_id'               => $this->user->id,
            'date'                  => now()->format('Y-m-d'),
            'type_id'               => $this->expenseType->id,
            'category_id'           => $this->category->id,
            'source_wallet_id'      => $this->wallet->id,
            'destination_wallet_id' => null,
            'amount'                => $amount,
            'balance_before'        => 200000.00,
            'balance_after'         => 200000.00 - $amount,
            'subject'               => $this->user->name,
            'notes'                 => "transaksi {$amount}",
            'is_cleared'            => true,
        ])->load(['category', 'sourceWallet', 'destinationWallet', 'type']);
    }

    // ══════════════════════════════════════════════════════════════════
    // Semua Sukses
    // ══════════════════════════════════════════════════════════════════

    /** @test */
    public function test_all_success_renders_success_header(): void
    {
        $trx1   = $this->makeTrx(15000.00);
        $trx2   = $this->makeTrx(30000.00);
        $result = new MultiTransactionResult(
            results: [
                MultiTransactionItem::success(1, $trx1, 'bensin 15k cash'),
                MultiTransactionItem::success(2, $trx2, 'makan 30k cash'),
            ],
            provider:   'GEMINI',
            model:      'gemini-1.5-flash',
            confidence: 0.95,
        );

        $output = $this->formatter->format($result);

        $this->assertStringContainsString('✅', $output);
        $this->assertStringContainsString('2', $output); // total count
    }

    /** @test */
    public function test_all_success_renders_rupiah_compact_format(): void
    {
        // KUNCI: format seharusnya "Rp20.000" (tanpa spasi, titik sebagai ribuan)
        // bukan "Rp 20,000" atau "Rp20000"
        $trx    = $this->makeTrx(20000.00);
        $result = new MultiTransactionResult(
            results: [
                MultiTransactionItem::success(1, $trx, 'bensin 20k cash'),
            ],
            provider:   'GEMINI',
            model:      'gemini-1.5-flash',
            confidence: 0.90,
        );

        $output = $this->formatter->format($result);

        $this->assertStringContainsString('Rp20.000', $output);
        // Pastikan TIDAK ada format lama yang salah
        $this->assertStringNotContainsString('Rp20,000', $output);
        $this->assertStringNotContainsString('Rp 20.000', $output); // rupiahCompact tidak ada spasi
    }

    /** @test */
    public function test_all_success_renders_each_item_with_index(): void
    {
        $trx1   = $this->makeTrx(15000.00);
        $trx2   = $this->makeTrx(50000.00);
        $result = new MultiTransactionResult(
            results: [
                MultiTransactionItem::success(1, $trx1, 'bensin 15k cash'),
                MultiTransactionItem::success(2, $trx2, 'makan 50k cash'),
            ],
            provider:   'OPENAI',
            model:      'gpt-4o-mini',
            confidence: 0.92,
        );

        $output = $this->formatter->format($result);

        $this->assertStringContainsString('1.', $output);
        $this->assertStringContainsString('2.', $output);
        $this->assertStringContainsString('Rp15.000', $output);
        $this->assertStringContainsString('Rp50.000', $output);
    }

    /** @test */
    public function test_all_success_shows_ai_provider_footer(): void
    {
        $trx    = $this->makeTrx(10000.00);
        $result = new MultiTransactionResult(
            results: [
                MultiTransactionItem::success(1, $trx, 'kopi 10k cash'),
            ],
            provider:   'GEMINI',
            model:      'gemini-1.5-flash',
            confidence: 0.88,
        );

        $output = $this->formatter->format($result);

        // Footer harus muncul jika ada sukses
        $this->assertStringContainsString('Gemini', $output);
        $this->assertStringContainsString('88%', $output); // confidence
    }

    // ══════════════════════════════════════════════════════════════════
    // Semua Gagal
    // ══════════════════════════════════════════════════════════════════

    /** @test */
    public function test_all_failed_renders_failure_header(): void
    {
        $result = new MultiTransactionResult(
            results: [
                MultiTransactionItem::failed(
                    index:     1,
                    raw:       'makan 20k spay',
                    errorCode: MultiTransactionErrorCode::WALLET_NOT_FOUND,
                    reason:    "Dompet 'spay' tidak ditemukan.",
                ),
                MultiTransactionItem::failed(
                    index:     2,
                    raw:       'bensin 50k ovo',
                    errorCode: MultiTransactionErrorCode::WALLET_NOT_FOUND,
                    reason:    "Dompet 'ovo' tidak ditemukan.",
                ),
            ],
            provider:   'GEMINI',
            model:      'gemini-1.5-flash',
            confidence: 0.70,
        );

        $output = $this->formatter->format($result);

        $this->assertStringContainsString('❌', $output);
        $this->assertStringContainsString('2', $output); // total count
    }

    /** @test */
    public function test_all_failed_shows_reason_for_each_item(): void
    {
        $result = new MultiTransactionResult(
            results: [
                MultiTransactionItem::failed(
                    index:     1,
                    raw:       'makan 20k spay',
                    errorCode: MultiTransactionErrorCode::WALLET_NOT_FOUND,
                    reason:    "Dompet 'spay' tidak ditemukan.",
                ),
            ],
            provider:   'GEMINI',
            model:      'gemini-1.5-flash',
            confidence: 0.60,
        );

        $output = $this->formatter->format($result);

        $this->assertStringContainsString('makan 20k spay', $output);
        $this->assertStringContainsString("Dompet 'spay' tidak ditemukan.", $output);
    }

    /** @test */
    public function test_all_failed_does_not_show_ai_footer(): void
    {
        $result = new MultiTransactionResult(
            results: [
                MultiTransactionItem::failed(
                    index:     1,
                    raw:       'test',
                    errorCode: MultiTransactionErrorCode::INVALID_AMOUNT,
                    reason:    'Nominal tidak valid.',
                ),
            ],
            provider:   'GEMINI',
            model:      'gemini-1.5-flash',
            confidence: 0.40,
        );

        $output = $this->formatter->format($result);

        // Footer provider TIDAK tampil jika semua gagal
        $this->assertStringNotContainsString('Gemini', $output);
        $this->assertStringNotContainsString('Keyakinan AI', $output);
    }

    // ══════════════════════════════════════════════════════════════════
    // Partial (Campur)
    // ══════════════════════════════════════════════════════════════════

    /** @test */
    public function test_partial_renders_mixed_success_and_failure(): void
    {
        $trx1   = $this->makeTrx(15000.00);
        $result = new MultiTransactionResult(
            results: [
                MultiTransactionItem::success(1, $trx1, 'bensin 15k cash'),
                MultiTransactionItem::failed(
                    index:     2,
                    raw:       'kopi 10k spay',
                    errorCode: MultiTransactionErrorCode::WALLET_NOT_FOUND,
                    reason:    "Dompet 'spay' tidak ditemukan.",
                ),
                MultiTransactionItem::failed(
                    index:     3,
                    raw:       'test',
                    errorCode: MultiTransactionErrorCode::INVALID_AMOUNT,
                    reason:    'Nominal tidak valid.',
                ),
            ],
            provider:   'DEEPSEEK',
            model:      'deepseek-chat',
            confidence: 0.80,
        );

        $output = $this->formatter->format($result);

        // Header partial: ada ✅ dan ❌
        $this->assertStringContainsString('✅', $output);
        $this->assertStringContainsString('❌', $output);
        // Item sukses
        $this->assertStringContainsString('Rp15.000', $output);
        $this->assertStringContainsString('1.', $output);
        // Item gagal
        $this->assertStringContainsString("Dompet 'spay' tidak ditemukan.", $output);
        $this->assertStringContainsString('kopi 10k spay', $output);
        // Footer muncul karena ada sukses
        $this->assertStringContainsString('DeepSeek', $output);
    }

    /** @test */
    public function test_partial_preserves_item_order(): void
    {
        $trx2   = $this->makeTrx(30000.00);
        $result = new MultiTransactionResult(
            results: [
                MultiTransactionItem::failed(
                    index:     1,
                    raw:       'item pertama gagal',
                    errorCode: MultiTransactionErrorCode::CATEGORY_NOT_FOUND,
                    reason:    'Kategori tidak terdeteksi.',
                ),
                MultiTransactionItem::success(2, $trx2, 'item kedua sukses'),
            ],
            provider:   'OPENAI',
            model:      'gpt-4o-mini',
            confidence: 0.75,
        );

        $output = $this->formatter->format($result);

        // Index 1 muncul sebelum index 2
        $pos1 = strpos($output, '1.');
        $pos2 = strpos($output, '2.');

        $this->assertNotFalse($pos1);
        $this->assertNotFalse($pos2);
        $this->assertLessThan($pos2, $pos1, 'Index 1 harus muncul sebelum index 2 dalam output.');
    }

    // ══════════════════════════════════════════════════════════════════
    // Error Codes
    // ══════════════════════════════════════════════════════════════════

    /** @test */
    public function test_renders_wallet_not_found_error_with_wallet_name(): void
    {
        $result = new MultiTransactionResult(
            results: [
                MultiTransactionItem::failed(
                    index:     1,
                    raw:       'transfer 100k ke mandiri',
                    errorCode: MultiTransactionErrorCode::WALLET_NOT_FOUND,
                    reason:    "Dompet 'mandiri' tidak ditemukan.",
                ),
            ],
            provider:   'GEMINI',
            model:      'gemini-1.5-flash',
            confidence: 0.55,
        );

        $output = $this->formatter->format($result);

        $this->assertStringContainsString('transfer 100k ke mandiri', $output);
        $this->assertStringContainsString("Dompet 'mandiri' tidak ditemukan.", $output);
    }

    /** @test */
    public function test_renders_category_not_found_error(): void
    {
        $result = new MultiTransactionResult(
            results: [
                MultiTransactionItem::failed(
                    index:     1,
                    raw:       'beli sesuatu 50k cash',
                    errorCode: MultiTransactionErrorCode::CATEGORY_NOT_FOUND,
                    reason:    'Kategori tidak terdeteksi oleh AI.',
                ),
            ],
            provider:   'GEMINI',
            model:      'gemini-1.5-flash',
            confidence: 0.50,
        );

        $output = $this->formatter->format($result);

        $this->assertStringContainsString('beli sesuatu 50k cash', $output);
        $this->assertStringContainsString('Kategori tidak terdeteksi oleh AI.', $output);
    }

    /** @test */
    public function test_renders_invalid_amount_error(): void
    {
        $result = new MultiTransactionResult(
            results: [
                MultiTransactionItem::failed(
                    index:     1,
                    raw:       'makan cash',
                    errorCode: MultiTransactionErrorCode::INVALID_AMOUNT,
                    reason:    'Nominal tidak valid atau nol.',
                ),
            ],
            provider:   'GEMINI',
            model:      'gemini-1.5-flash',
            confidence: 0.45,
        );

        $output = $this->formatter->format($result);

        $this->assertStringContainsString('makan cash', $output);
        $this->assertStringContainsString('Nominal tidak valid atau nol.', $output);
    }
}
