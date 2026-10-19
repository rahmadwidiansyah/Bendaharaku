<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\TransactionType;
use App\Models\User;
use App\Services\AI\AIManager;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIManagerMemoryInjectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
        config()->set('bendaharaku.ai.openai_compatible.api_key', '');
        config()->set('bendaharaku.ai.openai_compatible.base_url', '');
        config()->set('bendaharaku.ai.parser.python_first', true);
        config()->set('bendaharaku.ai.parser.min_confidence', 0.85);
    }

    /** @return array<string, mixed> */
    private function pythonOk(): array
    {
        return [
            'success' => true,
            'amount' => 20000,
            'transaction_type' => 'expense',
            'category' => 'Makanan',
            'source_wallet' => 'Dompet Cash',
            'destination_wallet' => null,
            'subject' => 'kopi',
            'confidence' => 0.92,
        ];
    }

    private function categoryPayload(string $name): array
    {
        $type = TransactionType::firstOrCreate(['name' => 'Expense'], ['keyword' => 'out']);

        $category = $this->user->categories()->create([
            'category_name' => $name,
            'keyword' => 'makan',
            'type_id' => $type->id,
        ]);

        return [
            'id' => $category->id,
            'category_name' => $category->category_name,
            'keyword' => $category->keyword,
        ];
    }

    private function walletPayload(string $name, ?string $keyword): array
    {
        $wallet = $this->user->wallets()->create([
            'name' => $name,
            'keyword' => $keyword,
            'balance' => 100000,
            'group_type' => 'Liquid',
        ]);

        return [
            'id' => $wallet->id,
            'name' => $wallet->name,
            'keyword' => $wallet->keyword,
        ];
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function capturedPythonPayload(): array
    {
        $captured = null;

        Http::assertSent(function ($request) use (&$captured) {
            if (! str_contains($request->url(), 'ai-parser')) {
                return false;
            }

            $captured = $request->data();

            return true;
        });

        $this->assertNotNull($captured, 'Python tidak menerima request.');

        return $captured;
    }

    public function test_memory_kategori_dan_wallet_disuntik_ke_payload_python(): void
    {
        $category = $this->categoryPayload('Makanan');
        $wallet = $this->walletPayload('Dompet Cash', 'cash');

        $activeMemories = [
            ['keyword' => 'kopi', 'category' => 'Makanan', 'wallet' => null],
            ['keyword' => 'makan', 'category' => 'Makanan', 'wallet' => null],
            ['keyword' => 'tunai', 'category' => null, 'wallet' => 'Dompet Cash'],
            ['keyword' => 'dompet', 'category' => null, 'wallet' => 'Dompet Cash'],
            ['keyword' => 'cash', 'category' => null, 'wallet' => 'Dompet Cash'],
            ['keyword' => 'ghost', 'category' => 'Tidak Ada', 'wallet' => 'Tidak Ada'],
        ];

        Http::fake(['ai-parser*' => Http::response($this->pythonOk(), 200)]);

        app(AIManager::class)->parseTransaction(
            $this->user,
            'kopi 20rb',
            [$wallet],
            [$category],
            $activeMemories,
            'prompt'
        );

        $payload = $this->capturedPythonPayload();

        $categoryEntry = collect($payload['categories'])->firstWhere('category_name', 'Makanan');
        $this->assertSame('makan,kopi', $categoryEntry['keyword']);

        $walletEntry = collect($payload['wallets'])->firstWhere('name', 'Dompet Cash');
        $this->assertSame('cash,tunai,dompet', $walletEntry['keyword']);
    }

    public function test_keyword_sentinel_dash_diisi_dan_tidak_ada_cross_leak(): void
    {
        $category = $this->categoryPayload('Makanan');
        $wallet = $this->walletPayload('GoPay', '-');

        $activeMemories = [
            ['keyword' => 'gopay', 'category' => null, 'wallet' => 'GoPay'],
            ['keyword' => 'e-wallet', 'category' => null, 'wallet' => 'GoPay'],
            ['keyword' => 'kopi', 'category' => 'Makanan', 'wallet' => null],
        ];

        Http::fake(['ai-parser*' => Http::response($this->pythonOk(), 200)]);

        app(AIManager::class)->parseTransaction(
            $this->user,
            'kopi 20rb',
            [$wallet],
            [$category],
            $activeMemories,
            'prompt'
        );

        $payload = $this->capturedPythonPayload();

        $walletEntry = collect($payload['wallets'])->firstWhere('name', 'GoPay');
        $this->assertSame('gopay,e-wallet', $walletEntry['keyword']);

        $categoryEntry = collect($payload['categories'])->firstWhere('category_name', 'Makanan');
        $this->assertStringContainsString('kopi', $categoryEntry['keyword']);
        $this->assertStringNotContainsString('gopay', $categoryEntry['keyword']);

        $walletRaw = json_encode($payload['wallets']);
        $this->assertStringNotContainsString('kopi', (string) $walletRaw);
    }

    public function test_target_tidak_cocok_diabaikan_tanpa_mengubah_payload(): void
    {
        $category = $this->categoryPayload('Makanan');
        $wallet = $this->walletPayload('Dompet Cash', 'cash');

        Http::fake(['ai-parser*' => Http::response($this->pythonOk(), 200)]);

        app(AIManager::class)->parseTransaction(
            $this->user,
            'kopi 20rb',
            [$wallet],
            [$category],
            [['keyword' => 'ghost', 'category' => 'Tidak Ada', 'wallet' => 'Tidak Ada']],
            'prompt'
        );

        $payload = $this->capturedPythonPayload();

        $this->assertSame('cash', collect($payload['wallets'])->firstWhere('name', 'Dompet Cash')['keyword']);
        $this->assertSame('makan', collect($payload['categories'])->firstWhere('category_name', 'Makanan')['keyword']);
    }
}
