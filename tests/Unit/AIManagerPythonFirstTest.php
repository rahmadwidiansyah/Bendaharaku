<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\AiProvider;
use App\Models\AiModelCatalog;
use App\Models\AiProviderAccount;
use App\Models\AiRoutingPreference;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\AI\AIManager;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIManagerPythonFirstTest extends TestCase
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
        config()->set('bendaharaku.ai.parser.min_confidence_fallback', 0.6);
    }

    private function makeAccount(string $key = 'test-key-12345678'): AiProviderAccount
    {
        $account = AiProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => AiProvider::Gemini->value,
            'alias' => 'G1',
            'api_key' => $key,
            'is_active' => true,
            'is_valid' => true,
            'priority' => 1,
            'health_status' => 'unknown',
        ]);

        AiModelCatalog::create([
            'account_id' => $account->id,
            'user_id' => $this->user->id,
            'provider' => AiProvider::Gemini->value,
            'model_id' => 'gemini-2.0-flash',
            'display_name' => 'gemini-2.0-flash',
            'supports_generate' => true,
            'category' => 'text_generation',
            'free_tier' => 'free',
            'free_tier_source' => 'test',
            'is_eligible' => true,
            'is_enabled' => true,
            'discovery_source' => 'test',
            'last_seen_at' => now(),
        ]);

        return $account;
    }

    private function routing(): void
    {
        AiRoutingPreference::updateOrCreate(
            ['user_id' => $this->user->id],
            [
                'mode' => 'automatic',
                'provider_priority' => ['gemini', 'openai', 'deepseek', 'openai-compatible'],
                'free_policy' => 'paid_allowed',
                'allow_paid_fallback' => true,
                'max_candidates' => 4,
                'max_attempts' => 8,
                'expand_beyond_primary' => true,
            ],
        );
    }

    /** @return array<string, mixed> */
    private function pythonOk(float $confidence): array
    {
        return [
            'success' => true,
            'amount' => 20000,
            'transaction_type' => 'expense',
            'category' => 'Makanan',
            'source_wallet' => 'Cash',
            'destination_wallet' => null,
            'subject' => 'bakso',
            'confidence' => $confidence,
        ];
    }

    /** @return array<string, mixed> */
    private function geminiOk(): array
    {
        $rawText = json_encode([
            'amount' => 20000,
            'category' => 'Makanan',
            'sourceWallet' => 'Cash',
            'transactionType' => 'expense',
        ]);

        return [
            'candidates' => [['content' => ['parts' => [['text' => $rawText]]]]],
            'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 5, 'totalTokenCount' => 15],
        ];
    }

    /** @param array<string, mixed> $python */
    private function fakeHttp(array $python, mixed $gemini): void
    {
        Http::fake([
            'ai-parser*' => Http::response($python, 200),
            'generativelanguage.googleapis.com/*' => $gemini,
        ]);
    }

    private function pythonCallCount(): int
    {
        return Http::recorded(fn ($request) => str_contains($request->url(), 'ai-parser'))->count();
    }

    /** 1. Python yakin (≥ primary) → pool tidak dipanggil, tanpa AiUsageLog, tanpa log fallback. */
    public function test_python_first_confident_skips_pool(): void
    {
        $this->makeAccount();
        $this->routing();
        $this->fakeHttp($this->pythonOk(0.92), Http::response($this->geminiOk(), 200));

        $result = app(AIManager::class)->parseTransaction($this->user, 'beli bakso 20rb cash', [], [], [], 'prompt');

        $this->assertTrue($result->success);
        $this->assertSame('python-nlp', $result->provider);
        $this->assertSame('local_python_primary', $result->routing['selection_method']);
        $this->assertSame('local_success', $result->routing['final_status']);
        $this->assertSame('python-nlp', $result->routing['selected_provider']);
        $this->assertNull($result->routing['fallback_reason'] ?? null);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'generativelanguage'));
        $this->assertSame(0, AiUsageLog::where('user_id', $this->user->id)->count());
        $this->assertSame(0, UserActivityLog::where('user_id', $this->user->id)->where('action', 'fallback')->count());
    }

    /** 2. Python di bawah primary → pool LLM dipakai. */
    public function test_python_not_confident_falls_back_to_pool(): void
    {
        $this->makeAccount();
        $this->routing();
        $this->fakeHttp($this->pythonOk(0.7), Http::response($this->geminiOk(), 200));

        $result = app(AIManager::class)->parseTransaction($this->user, 'beli bakso 20rb cash', [], [], [], 'prompt');

        $this->assertTrue($result->success);
        $this->assertSame('gemini', $result->provider);
        $this->assertSame(1, $this->pythonCallCount());
    }

    /** 3. Pool habis → Python diterima di ambang fallback (0.6), hasil awal dipakai ulang (1 HTTP call). */
    public function test_pool_down_accepts_python_at_fallback_threshold(): void
    {
        $this->makeAccount();
        $this->routing();
        $this->fakeHttp($this->pythonOk(0.7), Http::response(['error' => 'bad'], 401));

        $result = app(AIManager::class)->parseTransaction($this->user, 'beli bakso 20rb cash', [], [], [], 'prompt');

        $this->assertTrue($result->success);
        $this->assertSame('python-nlp', $result->provider);
        $this->assertSame('local_python_fallback', $result->routing['selection_method']);
        $this->assertSame('local_fallback', $result->routing['final_status']);
        $this->assertNotNull($result->routing['fallback_reason'] ?? null);
        $this->assertSame(1, $this->pythonCallCount());
        $this->assertSame(1, UserActivityLog::where('user_id', $this->user->id)->where('action', 'fallback')->count());
    }

    /** 4. Pool habis & Python di bawah ambang fallback → throw (LRE tanpa amount juga gagal). */
    public function test_pool_down_rejects_python_below_fallback_threshold(): void
    {
        $this->makeAccount();
        $this->routing();
        $this->fakeHttp($this->pythonOk(0.5), Http::response(['error' => 'bad'], 401));

        try {
            app(AIManager::class)->parseTransaction($this->user, 'halo apa kabar tanpa angka', [], [], [], 'prompt');
            $this->fail('Seharusnya throw — Python < ambang fallback & LRE gagal.');
        } catch (\Throwable) {
            $this->assertSame(1, $this->pythonCallCount());
        }
    }

    /** 5. Python down → pool normal tanpa penundaan gate. */
    public function test_python_down_uses_pool(): void
    {
        $this->makeAccount();
        $this->routing();
        $this->fakeHttp(['success' => false], Http::response($this->geminiOk(), 200));

        $result = app(AIManager::class)->parseTransaction($this->user, 'beli bakso 20rb cash', [], [], [], 'prompt');

        $this->assertTrue($result->success);
        $this->assertSame('gemini', $result->provider);
    }

    /** 6. Kill-switch: python_first=false → pool dulu, Python tidak dipanggil saat pool sukses. */
    public function test_python_first_disabled_pool_wins(): void
    {
        config()->set('bendaharaku.ai.parser.python_first', false);
        $this->makeAccount();
        $this->routing();
        $this->fakeHttp($this->pythonOk(0.95), Http::response($this->geminiOk(), 200));

        $result = app(AIManager::class)->parseTransaction($this->user, 'beli bakso 20rb cash', [], [], [], 'prompt');

        $this->assertTrue($result->success);
        $this->assertSame('gemini', $result->provider);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'ai-parser'));
    }

    /** 7. Ambang fallback konfigurabel: dinaikkan → Python 0.7 ditolak saat pool habis. */
    public function test_fallback_threshold_is_configurable(): void
    {
        config()->set('bendaharaku.ai.parser.min_confidence_fallback', 0.75);
        $this->makeAccount();
        $this->routing();
        $this->fakeHttp($this->pythonOk(0.7), Http::response(['error' => 'bad'], 401));

        try {
            app(AIManager::class)->parseTransaction($this->user, 'halo apa kabar tanpa angka', [], [], [], 'prompt');
            $this->fail('Seharusnya throw — 0.7 < ambang fallback kustom 0.75.');
        } catch (\Throwable) {
            $this->assertSame(1, $this->pythonCallCount());
        }
    }

    /** 8. Ambang primary konfigurabel: diturunkan → Python 0.7 langsung short-circuit. */
    public function test_primary_threshold_is_configurable(): void
    {
        config()->set('bendaharaku.ai.parser.min_confidence', 0.65);
        $this->makeAccount();
        $this->routing();
        $this->fakeHttp($this->pythonOk(0.7), Http::response($this->geminiOk(), 200));

        $result = app(AIManager::class)->parseTransaction($this->user, 'beli bakso 20rb cash', [], [], [], 'prompt');

        $this->assertSame('python-nlp', $result->provider);
        $this->assertSame('local_python_primary', $result->routing['selection_method']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'generativelanguage'));
    }
}
