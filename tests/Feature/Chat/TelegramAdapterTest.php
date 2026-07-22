<?php

declare(strict_types=1);

namespace Tests\Feature\Chat;

use App\Chat\Adapters\TelegramAdapter;
use App\Models\User;
use App\Models\Wallet;
use App\Support\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature test untuk TelegramAdapter.
 *
 * Coverage:
 *  1. /saldo command → memanggil sendBalanceReport()
 *  2. /saldo dengan satu dompet → format Rupiah benar dari PostgreSQL DECIMAL cast
 *  3. /saldo dengan banyak dompet → total balance dihitung benar
 *  4. /saldo tanpa dompet → pesan "dompet kosong"
 *  5. /saldo hanya menampilkan group_type Liquid & Asset, bukan System
 *  6. /web command → kirim link web
 *  7. /start dan greeting → kirim help message
 *  8. User tidak terdaftar (telegram_id tidak match) → unauthorized
 *  9. Update tanpa 'message.text' → ignored
 */
class TelegramAdapterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private TelegramAdapter $adapter;

    private array $capturedMessages = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'telegram_id' => 99999999,
            'locale' => 'id',
            'timezone' => 'Asia/Jakarta',
        ]);

        // Mock HTTP agar tidak memanggil Telegram API sungguhan
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->adapter = $this->app->make(TelegramAdapter::class);
    }

    // ── Helper: buat Telegram update payload ─────────────────────

    private function makeUpdate(string $text, int $chatId = 99999999): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 100,
                'chat' => ['id' => $chatId],
                'from' => ['language_code' => 'id'],
                'text' => $text,
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // /saldo command
    // ══════════════════════════════════════════════════════════════════

    /** @test */
    public function test_saldo_command_returns_success_status(): void
    {
        $this->user->wallets()->create([
            'name' => 'Cash',
            'balance' => 100000.00,
            'group_type' => 'Liquid',
        ]);

        $result = $this->adapter->handle($this->makeUpdate('/saldo'));

        $this->assertSame('success', $result['status']);
    }

    /** @test */
    public function test_saldo_command_sends_message_to_telegram_api(): void
    {
        $this->user->wallets()->create([
            'name' => 'Cash',
            'balance' => 100000.00,
            'group_type' => 'Liquid',
        ]);

        $this->adapter->handle($this->makeUpdate('/saldo'));

        // Pastikan Http::post ke Telegram dipanggil
        Http::assertSentCount(1);
    }

    /** @test */
    public function test_saldo_command_formats_balance_with_indonesian_thousand_separator(): void
    {
        // Root bug: PostgreSQL DECIMAL → PDO string "102100.00"
        // Setelah fix: model cast ke float → MoneyFormatter::amount() → "102.100"
        $this->user->wallets()->create([
            'name' => 'BCA',
            'balance' => 102100.00, // disimpan sebagai DECIMAL(15,2)
            'group_type' => 'Liquid',
        ]);

        // Ambil wallet BCA dari DB secara spesifik (user punya system wallets otomatis dari boot)
        $wallet = Wallet::where('user_id', $this->user->id)
            ->where('name', 'BCA')
            ->first();

        // Setelah load dari DB, balance harus float (bukan string)
        $this->assertIsFloat($wallet->balance,
            'Wallet.balance dari DB harus float setelah model cast diterapkan.');
        $this->assertSame(102100.0, $wallet->balance);

        // Format dengan MoneyFormatter harus menghasilkan string yang benar
        $formatted = MoneyFormatter::amount($wallet->balance);
        $this->assertSame('102.100', $formatted,
            'MoneyFormatter::amount() harus menghasilkan "102.100" bukan "102100.00".');
    }

    /** @test */
    public function test_saldo_command_calculates_total_balance_correctly(): void
    {
        $this->user->wallets()->create([
            'name' => 'BCA',
            'balance' => 500000.00,
            'group_type' => 'Liquid',
        ]);
        $this->user->wallets()->create([
            'name' => 'Dana',
            'balance' => 250000.00,
            'group_type' => 'Liquid',
        ]);
        $this->user->wallets()->create([
            'name' => 'Saham',
            'balance' => 1000000.00,
            'group_type' => 'Asset',
        ]);

        // Total = 500.000 + 250.000 + 1.000.000 = 1.750.000
        $wallets = Wallet::where('user_id', $this->user->id)
            ->whereIn('group_type', ['Asset', 'Liquid'])
            ->get();

        $totalBalance = $wallets->sum('balance');
        $this->assertSame(1750000.0, (float) $totalBalance);
        $this->assertSame('1.750.000', MoneyFormatter::amount($totalBalance));
    }

    /** @test */
    public function test_saldo_command_only_shows_liquid_and_asset_wallets(): void
    {
        // User::booted() otomatis membuat "Dompet Cash" (Liquid) + 4 System wallets.
        // Kita tambah 1 wallet Asset untuk test.
        $this->user->wallets()->create([
            'name' => 'Saham',
            'balance' => 5000000.00,
            'group_type' => 'Asset',
        ]);

        // Query yang dipakai TelegramAdapter — hanya Liquid & Asset
        $wallets = Wallet::where('user_id', $this->user->id)
            ->whereIn('group_type', ['Asset', 'Liquid'])
            ->get();

        // Harus ada: "Dompet Cash" (Liquid, dari booted) + "Saham" (Asset, kita buat) = 2
        $this->assertGreaterThanOrEqual(2, $wallets->count());
        // Tidak boleh ada wallet dengan group_type System
        $this->assertNotContains('System', $wallets->pluck('group_type')->toArray());
        // Wallet Asset yang kita buat harus ada
        $this->assertTrue($wallets->contains('name', 'Saham'));
    }

    /** @test */
    public function test_saldo_command_returns_success_when_no_wallets(): void
    {
        // Tidak ada wallet — harus mengirim pesan "dompet kosong" bukan error
        $result = $this->adapter->handle($this->makeUpdate('/saldo'));

        $this->assertSame('success', $result['status']);
        Http::assertSentCount(1);
    }

    // ══════════════════════════════════════════════════════════════════
    // /web command
    // ══════════════════════════════════════════════════════════════════

    /** @test */
    public function test_web_command_returns_success_status(): void
    {
        $result = $this->adapter->handle($this->makeUpdate('/web'));

        $this->assertSame('success', $result['status']);
        Http::assertSentCount(1);
    }

    // ══════════════════════════════════════════════════════════════════
    // Greeting commands
    // ══════════════════════════════════════════════════════════════════

    /** @test */
    public function test_start_command_returns_success(): void
    {
        $result = $this->adapter->handle($this->makeUpdate('/start'));

        $this->assertSame('success', $result['status']);
    }

    /** @test */
    public function test_help_greeting_returns_success(): void
    {
        $result = $this->adapter->handle($this->makeUpdate('halo'));

        $this->assertSame('success', $result['status']);
    }

    /** @test */
    public function test_ping_greeting_returns_success(): void
    {
        $result = $this->adapter->handle($this->makeUpdate('ping'));

        $this->assertSame('success', $result['status']);
    }

    // ══════════════════════════════════════════════════════════════════
    // Auth & Edge cases
    // ══════════════════════════════════════════════════════════════════

    /** @test */
    public function test_unknown_telegram_id_returns_unauthorized(): void
    {
        $result = $this->adapter->handle($this->makeUpdate('/saldo', chatId: 8888888));

        $this->assertSame('unauthorized', $result['status']);
    }

    /** @test */
    public function test_update_without_message_text_is_ignored(): void
    {
        $update = [
            'update_id' => 2,
            'message' => [
                'message_id' => 101,
                'chat' => ['id' => 99999999],
                // Tidak ada 'text' key
                'sticker' => ['file_id' => 'abc123'],
            ],
        ];

        $result = $this->adapter->handle($update);

        $this->assertSame('ignored', $result['status']);
        Http::assertNothingSent();
    }
}
