<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\AIParseResult;
use App\DTO\ChatProcessOptions;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\AI\Providers\PythonNLPProvider;
use App\Services\Chat\CommaMultiParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CommaMultiParserTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private CommaMultiParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $incomeType = TransactionType::create(['name' => 'Income']);
        $expenseType = TransactionType::create(['name' => 'Expense']);
        $transferType = TransactionType::create(['name' => 'Transfer']);

        $this->user = User::factory()->create(['name' => 'Tester']);
        $this->user->wallets()->forceDelete();
        $this->user->categories()->forceDelete();

        $this->user->wallets()->createMany([
            ['name' => 'Dompet Cash', 'keyword' => 'cash, tunai', 'group_type' => 'Liquid', 'balance' => 500000],
            ['name' => 'Seabank', 'keyword' => 'seabank', 'group_type' => 'Liquid', 'balance' => 1000000],
            ['name' => 'Dana', 'keyword' => 'dana', 'group_type' => 'Liquid', 'balance' => 300000],
            ['name' => 'ShopeePay', 'keyword' => 'spay, shopeepay', 'group_type' => 'Liquid', 'balance' => 200000],
            ['name' => 'External System', 'group_type' => 'System'],
            ['name' => 'Merchant System', 'group_type' => 'System'],
            ['name' => 'System Hutang', 'group_type' => 'System'],
            ['name' => 'System Piutang', 'group_type' => 'System'],
        ]);

        $this->user->categories()->createMany([
            ['category_name' => 'Makan & Minum', 'type_id' => $expenseType->id, 'keyword' => 'makan, bakso'],
            ['category_name' => 'Gaji', 'type_id' => $incomeType->id, 'keyword' => 'gaji'],
            ['category_name' => 'Pindah Saldo', 'type_id' => $transferType->id, 'keyword' => 'transfer, pindah saldo'],
        ]);

    }

    /**
     * Parser DI-fresh SETELAH mock PythonNLPProvider ter-bind pada container.
     */
    private function makeParser(): CommaMultiParser
    {
        return app(CommaMultiParser::class);
    }

    // ── splitSegments (via reflection) ──────────────────────────────

    private function split(string $text): ?array
    {
        $ref = new \ReflectionMethod($this->makeParser(), 'splitSegments');
        $ref->setAccessible(true);

        return $ref->invoke($this->makeParser(), $text);
    }

    /** @test */
    public function test_contoh_user_empat_transaksi_koma_terpecah_empat(): void
    {
        $segments = $this->split('beli makan 20k tunai, beli bakso 10k seabank, dapet gaji 20k dana, pindah saldo dana ke spay 200k');

        $this->assertIsArray($segments);
        $this->assertCount(4, $segments);
        $this->assertSame('beli makan 20k tunai', $segments[0]);
        $this->assertSame('beli bakso 10k seabank', $segments[1]);
        $this->assertSame('dapet gaji 20k dana', $segments[2]);
        $this->assertSame('pindah saldo dana ke spay 200k', $segments[3]);
    }

    /** @test */
    public function test_desimal_indonesia_tidak_terpecah(): void
    {
        // "1,5 juta" = satu koma-lima juta — koma diikuti digit, bukan spasi
        $segments = $this->split('gaji 1,5 juta dana, beli bakso 10k cash');

        $this->assertIsArray($segments);
        $this->assertCount(2, $segments);
        $this->assertSame('gaji 1,5 juta dana', $segments[0]);
    }

    /** @test */
    public function test_ribuan_gaya_inggris_tidak_terpecah(): void
    {
        $segments = $this->split('gaji 1,000,000 cash');

        $this->assertNull($segments, 'Satu nominal dengan pemisah ribuan bukan multi-transaksi');
    }

    /** @test */
    public function test_koma_diikuti_spasi_wajib_untuk_split(): void
    {
        // Koma tanpa spasi ("20k,bakso") dianggap bagian dari angka/teks —
        // bukan pemisah yang aman
        $segments = $this->split('beli makan 20k tunai,beli bakso 10k seabank');

        $this->assertNull($segments);
    }

    /** @test */
    public function test_segmen_tanpa_nominal_ditolak(): void
    {
        // "dan jangan lupa" tanpa nominal → bukan pola daftar transaksi
        $segments = $this->split('beli bakso 10k cash, dan jangan lupa besok');

        $this->assertNull($segments);
    }

    /** @test */
    public function test_lebih_dari_batas_segmen_ditolak(): void
    {
        $text = implode(', ', array_map(
            fn (int $i) => "beli barang{$i} {$i}0k cash",
            range(1, 11),
        ));

        $this->assertNull($this->split($text));
    }

    // ── tryProcess (gate + jalur sukses) ────────────────────────────

    private function fakePython(array $confidences, array $transactions): void
    {
        $results = array_map(
            fn ($i) => new AIParseResult(
                success: true,
                confidence: $confidences[$i],
                error: null,
                transaction: $transactions[$i],
                provider: 'python-nlp',
                model: 'local',
            ),
            array_keys($transactions),
        );

        $mock = Mockery::mock(PythonNLPProvider::class);
        // Sekuen return; gate boleh berhenti lebih awal sehingga sisa tak terpakai
        $mock->shouldReceive('parseTransaction')->andReturn(...$results);
        $this->app->instance(PythonNLPProvider::class, $mock);
    }

    private function parsedTx(float $amount, string $category, string $wallet, TransactionIntent $intent = TransactionIntent::Expense, ?string $sourceWalletOverride = null): ParsedTransaction
    {
        return new ParsedTransaction(
            amount: $amount,
            transactionType: $intent,
            category: $category,
            sourceWallet: $intent === TransactionIntent::Transfer ? ($sourceWalletOverride ?? 'Dana') : null,
            destinationWallet: $wallet,
            subject: 'Tester',
        );
    }

    /** @test */
    public function test_semua_segmen_lolos_ambang_hasil_multi_tanpa_llm(): void
    {
        // Contoh user: 4 transaksi dipisah koma
        $this->fakePython(
            [0.95, 0.92, 0.9, 0.88],
            [
                $this->parsedTx(20000, 'Makan & Minum', 'Dompet Cash'),
                $this->parsedTx(10000, 'Makan & Minum', 'Seabank'),
                $this->parsedTx(20000, 'Gaji', 'Dana', TransactionIntent::Income),
                $this->parsedTx(200000, 'Pindah Saldo', 'ShopeePay', TransactionIntent::Transfer, 'Dana'),
            ],
        );

        $result = $this->makeParser()->tryProcess(
            $this->user,
            'beli makan 20k tunai, beli bakso 10k seabank, dapet gaji 20k dana, pindah saldo dana ke spay 200k',
            $this->user->wallets()->where('group_type', '!=', 'System')->get()->toArray(),
            $this->user->categories()->get()->toArray(),
            [],
            'TEL',
            new ChatProcessOptions,
        );

        $this->assertNotNull($result, 'Semua segmen lolos ambang → tidak boleh fallback ke LLM');
        $this->assertTrue($result['is_multi']);
        $this->assertTrue($result['success']);

        $multi = $result['multi_result'];
        $this->assertSame(4, $multi->totalCount());
        $this->assertSame(4, $multi->successCount());
        $this->assertSame('python-nlp', $multi->provider);
        $this->assertEqualsWithDelta(0.88, $multi->confidence, 0.0001, 'Confidence gabungan = skor TERENDAH antar segmen');
        $this->assertSame('local_comma_split', $multi->routing['selection_method']);
        $this->assertSame(4, $multi->routing['segment_count']);
    }

    /** @test */
    public function test_satu_segmen_skor_rendah_fallback_ke_llm(): void
    {
        $this->fakePython(
            [0.95, 0.40, 0.9, 0.88],
            [
                $this->parsedTx(20000, 'Makan & Minum', 'Dompet Cash'),
                $this->parsedTx(10000, 'Makan & Minum', 'Seabank'),
                $this->parsedTx(20000, 'Gaji', 'Dana', TransactionIntent::Income),
                $this->parsedTx(200000, 'Pindah Saldo', 'ShopeePay', TransactionIntent::Transfer, 'Dana'),
            ],
        );

        $result = $this->makeParser()->tryProcess(
            $this->user,
            'beli makan 20k tunai, beli bakso 10k seabank, dapet gaji 20k dana, pindah saldo dana ke spay 200k',
            $this->user->wallets()->where('group_type', '!=', 'System')->get()->toArray(),
            $this->user->categories()->get()->toArray(),
            [],
            'TEL',
            new ChatProcessOptions,
        );

        $this->assertNull($result, 'Ada segmen di bawah ambang → caller harus fallback ke LLM');
    }

    /** @test */
    public function test_segmen_tanpa_kategori_fallback_ke_llm(): void
    {
        // Python "berhasil" tapi kategori kosong → guard gagal → LLM
        $tx = new ParsedTransaction(
            amount: 20000,
            transactionType: TransactionIntent::Expense,
            category: null,
            sourceWallet: null,
            destinationWallet: 'Dompet Cash',
            subject: 'Tester',
        );
        $this->fakePython([0.99], [$tx]);

        $result = $this->makeParser()->tryProcess(
            $this->user,
            'beli sesuatu 20k tunai, beli bakso 10k seabank',
            $this->user->wallets()->where('group_type', '!=', 'System')->get()->toArray(),
            $this->user->categories()->get()->toArray(),
            [],
            'TEL',
            new ChatProcessOptions,
        );

        $this->assertNull($result);
    }

    /** @test */
    public function test_teks_tanpa_koma_bukan_wewenang_parser_ini(): void
    {
        $this->assertNull($this->makeParser()->tryProcess(
            $this->user,
            'beli bakso 10k seabank',
            $this->user->wallets()->where('group_type', '!=', 'System')->get()->toArray(),
            $this->user->categories()->get()->toArray(),
            [],
            'TEL',
            new ChatProcessOptions,
        ));
    }
}
