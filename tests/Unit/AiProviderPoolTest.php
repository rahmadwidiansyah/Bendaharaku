<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\AIParseResult;
use App\DTO\AIParseResultMulti;
use App\Enums\AiProvider;
use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Models\AiModelCatalog;
use App\Models\AiProviderAccount;
use App\Models\AiRoutingPreference;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\AI\AIManager;
use App\Services\AI\Discovery\ModelDiscoveryService;
use App\Services\AI\Discovery\ModelNormalizer;
use App\Services\AI\Routing\AiErrorClassifier;
use App\Services\AI\Routing\AiRoutingExecutor;
use App\Services\AI\Routing\ModelResolver;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderPoolTest extends TestCase
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
    }

    private function makeAccount(AiProvider $provider, string $alias, string $key = 'test-key-12345678', int $priority = 1): AiProviderAccount
    {
        return AiProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => $provider->value,
            'alias' => $alias,
            'api_key' => $key,
            'is_active' => true,
            'is_valid' => true,
            'priority' => $priority,
            'health_status' => 'unknown',
        ]);
    }

    private function addModel(AiProviderAccount $account, string $modelId, string $freeTier = 'free', bool $eligible = true): AiModelCatalog
    {
        return AiModelCatalog::create([
            'account_id' => $account->id,
            'user_id' => $this->user->id,
            'provider' => $account->provider->value ?? (string) $account->provider,
            'model_id' => $modelId,
            'display_name' => $modelId,
            'supports_generate' => true,
            'category' => 'text_generation',
            'free_tier' => $freeTier,
            'free_tier_source' => 'test',
            'is_eligible' => $eligible,
            'is_enabled' => true,
            'discovery_source' => 'test',
            'last_seen_at' => now(),
        ]);
    }

    private function routing(array $overrides = []): AiRoutingPreference
    {
        return AiRoutingPreference::updateOrCreate(
            ['user_id' => $this->user->id],
            array_merge([
                'mode' => 'automatic',
                'provider_priority' => ['gemini', 'openai', 'deepseek', 'openai-compatible'],
                'free_policy' => 'paid_allowed',
                'allow_paid_fallback' => true,
                'max_candidates' => 4,
                'max_attempts' => 8,
                'expand_beyond_primary' => true,
            ], $overrides),
        );
    }

    private function okResult(string $provider, string $model): AIParseResult
    {
        return new AIParseResult(true, 0.9, null, null, ['prompt' => 1, 'completion' => 1, 'total' => 2], $provider, $model);
    }

    /** 1. Model utama berhasil tanpa fallback. */
    public function test_primary_model_success_without_fallback(): void
    {
        $acc = $this->makeAccount(AiProvider::Gemini, 'A1');
        $this->addModel($acc, 'gemini-2.0-flash');
        $this->routing();
        $ex = app(AiRoutingExecutor::class);
        $calls = [];
        $out = $ex->executeParse($this->user, function ($account, $model, $provider) use (&$calls) {
            $calls[] = [$provider->value, $account->id, $model];

            return $this->okResult($provider->value, $model);
        }, null, fn () => null);
        $this->assertSame('gemini', $out['routing']['selected_provider']);
        $this->assertSame(1, $out['routing']['attempt_count']);
        $this->assertCount(1, $calls);
    }

    /** 2. Model utama gagal → model alternatif akun sama berhasil. */
    public function test_model_failover_same_account(): void
    {
        $acc = $this->makeAccount(AiProvider::Gemini, 'A1');
        $this->addModel($acc, 'gemini-2.0-flash');
        $this->addModel($acc, 'gemini-2.5-flash');
        $this->routing(['mode' => 'manual', 'primary_provider' => 'gemini', 'primary_model' => 'gemini-2.0-flash',
            'fallback_models' => [['provider' => 'gemini', 'model' => 'gemini-2.5-flash']]]);
        $ex = app(AiRoutingExecutor::class);
        $out = $ex->executeParse($this->user, function ($account, $model) {
            if ($model === 'gemini-2.0-flash') {
                throw new AiProviderException('gemini', 'HTTP 404: model not found');
            }

            return $this->okResult('gemini', $model);
        }, null, fn () => null);
        $this->assertSame('gemini-2.5-flash', $out['routing']['selected_model']);
        $this->assertSame(2, $out['routing']['attempt_count']);
        $this->assertSame('gemini/gemini-2.0-flash', $out['routing']['fallback_from']);
        $this->assertSame('gemini/gemini-2.5-flash', $out['routing']['fallback_to']);
        $this->assertSame(AiErrorClassifier::CAT_MODEL_NOT_FOUND, $out['routing']['fallback_reason']);
        // Model 404 otomatis dinonaktifkan.
        $this->assertFalse(AiModelCatalog::where('model_id', 'gemini-2.0-flash')->first()->is_enabled);
    }

    /** Fallback sukses via AIManager mencatat tepat satu activity log. */
    public function test_pool_fallback_success_logs_single_activity_entry(): void
    {
        $acc = $this->makeAccount(AiProvider::Gemini, 'G1');
        $this->addModel($acc, 'gemini-2.0-flash');
        $this->addModel($acc, 'gemini-2.5-flash');
        $this->routing(['mode' => 'manual', 'primary_provider' => 'gemini', 'primary_model' => 'gemini-2.0-flash',
            'fallback_models' => [['provider' => 'gemini', 'model' => 'gemini-2.5-flash']]]);

        $rawText = json_encode([
            'amount' => 20000,
            'category' => 'Makanan',
            'sourceWallet' => 'Cash',
            'transactionType' => 'expense',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['error' => ['code' => 404, 'message' => 'model not found']], 404)
                ->push([
                    'candidates' => [['content' => ['parts' => [['text' => $rawText]]]]],
                    'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 5, 'totalTokenCount' => 15],
                ], 200),
        ]);

        $result = app(AIManager::class)->parseTransaction($this->user, 'makan 20rb', [], [], [], 'prompt');

        $this->assertTrue($result->success);
        $this->assertSame('gemini/gemini-2.0-flash', $result->routing['fallback_from']);
        $this->assertSame('gemini/gemini-2.5-flash', $result->routing['fallback_to']);

        $logs = UserActivityLog::where('user_id', $this->user->id)
            ->where('type', 'ai')
            ->where('action', 'fallback')
            ->get();

        $this->assertCount(1, $logs);
        $this->assertSame('AI fallback: gemini/gemini-2.0-flash → gemini/gemini-2.5-flash', $logs->first()->title);
    }

    /** 3. Seluruh kandidat akun 1 gagal → akun 2 berhasil. */
    public function test_account_failover(): void
    {
        $a1 = $this->makeAccount(AiProvider::Gemini, 'A1', 'key-aaaaaaaaaaaaaaaa', 1);
        $a2 = $this->makeAccount(AiProvider::Gemini, 'A2', 'key-bbbbbbbbbbbbbbbb', 2);
        $this->addModel($a1, 'gemini-2.0-flash');
        $this->addModel($a2, 'gemini-2.0-flash');
        $this->routing();
        $ex = app(AiRoutingExecutor::class);
        $out = $ex->executeParse($this->user, function ($account) use ($a1) {
            if ($account->id === $a1->id) {
                throw new AiRateLimitException('gemini');
            }

            return $this->okResult('gemini', 'gemini-2.0-flash');
        }, null, fn () => null);
        $this->assertSame($a2->id, $out['routing']['selected_account_id']);
    }

    /** 4. Semua Gemini gagal → OpenAI berhasil. */
    public function test_provider_failover_gemini_to_openai(): void
    {
        $g = $this->makeAccount(AiProvider::Gemini, 'G1');
        $o = $this->makeAccount(AiProvider::OpenAI, 'O1');
        $this->addModel($g, 'gemini-2.0-flash');
        $this->addModel($o, 'gpt-4o-mini');
        $this->routing();
        $ex = app(AiRoutingExecutor::class);
        $out = $ex->executeParse($this->user, function ($account, $model, $provider) {
            if ($provider === AiProvider::Gemini) {
                throw new AiRateLimitException('gemini');
            }

            return $this->okResult($provider->value, $model);
        }, null, fn () => null);
        $this->assertSame('openai', $out['routing']['selected_provider']);
    }

    /** 5. OpenAI gagal → OpenAI-compatible berhasil. */
    public function test_openai_to_compatible_failover(): void
    {
        $o = $this->makeAccount(AiProvider::OpenAI, 'O1');
        $c = $this->makeAccount(AiProvider::OpenAiCompatible, 'C1');
        $c->update(['base_url' => 'https://example.com/v1/chat/completions']);
        $this->addModel($o, 'gpt-4o-mini');
        $this->addModel($c, 'gpt-4o-mini');
        $this->routing(['provider_priority' => ['openai', 'openai-compatible', 'gemini', 'deepseek']]);
        $ex = app(AiRoutingExecutor::class);
        $out = $ex->executeParse($this->user, function ($account, $model, $provider) {
            if ($provider === AiProvider::OpenAI) {
                throw new AiProviderException('openai', 'HTTP 500: server error');
            }

            return $this->okResult($provider->value, $model);
        }, null, fn () => null);
        $this->assertSame('openai-compatible', $out['routing']['selected_provider']);
    }

    /** 6-8. Python-first down + pool habis → LRE gagal → throw; Python tepat 1 HTTP call (hasil di-hold). */
    public function test_aimanager_local_fallback_chain(): void
    {
        $acc = $this->makeAccount(AiProvider::Gemini, 'G1', 'bad-key');
        $this->addModel($acc, 'gemini-2.0-flash');
        $this->routing();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'bad'], 401),
            'ai-parser*' => Http::response(['success' => false], 500),
        ]);
        $manager = app(AIManager::class);
        // LRE real akan return null untuk teks tanpa amount → expect AiProviderException.
        try {
            $manager->parseTransaction($this->user, 'halo apa kabar tanpa angka', [], [], [], 'prompt');
            $this->fail('Seharusnya throw — semua jalur lokal gagal.');
        } catch (\Throwable) {
            $pythonCalls = Http::recorded(fn ($request) => str_contains($request->url(), 'ai-parser'))->count();
            $this->assertSame(1, $pythonCalls, 'Python harus dipanggil sekali (hasil di-hold untuk fallback).');
        }
    }

    /** 9. Key invalid hanya tandai akun tsb, akun lain tetap sehat. */
    public function test_invalid_key_only_marks_that_account(): void
    {
        $a1 = $this->makeAccount(AiProvider::Gemini, 'A1', 'key-aaaaaaaaaaaaaaaa');
        $a2 = $this->makeAccount(AiProvider::Gemini, 'A2', 'key-bbbbbbbbbbbbbbbb');
        $this->addModel($a1, 'gemini-2.0-flash');
        $this->addModel($a2, 'gemini-2.0-flash');
        $this->routing();
        $ex = app(AiRoutingExecutor::class);
        $out = $ex->executeParse($this->user, function ($account) use ($a1) {
            if ($account->id === $a1->id) {
                throw new AiProviderException('gemini', 'API Key tidak valid (HTTP 401).');
            }

            return $this->okResult('gemini', 'x');
        }, null, fn () => null);
        $this->assertSame($a2->id, $out['routing']['selected_account_id']);
        $this->assertFalse($a1->fresh()->is_valid);
        $this->assertSame('invalid', $a1->fresh()->health_status);
        $this->assertTrue($a2->fresh()->is_valid);
    }

    /** 10. Rate limit model (tanpa kata quota) → NEXT_MODEL, bukan skip akun. */
    public function test_rate_limit_model_scope(): void
    {
        $c = AiErrorClassifier::classify(new AiRateLimitException('gemini'));
        $this->assertSame(AiErrorClassifier::CAT_RATE_LIMIT_MODEL, $c['category']);
        $this->assertSame(AiErrorClassifier::ACTION_NEXT_MODEL, $c['action']);
        $quota = AiErrorClassifier::classify(new \Exception('gemini: HTTP 429: quota exceeded for project'));
        $this->assertSame(AiErrorClassifier::CAT_QUOTA, $quota['category']);
        $this->assertSame(AiErrorClassifier::ACTION_NEXT_ACCOUNT, $quota['action']);
    }

    /** 11. Model 404 tidak dipilih terus (dinonaktifkan). */
    public function test_model_not_found_disabled(): void
    {
        $c = AiErrorClassifier::classify(new AiProviderException('gemini', 'HTTP 404: model not found'));
        $this->assertSame(AiErrorClassifier::CAT_MODEL_NOT_FOUND, $c['category']);
    }

    /** 12. free-only menolak paid & unknown. */
    public function test_free_only_policy(): void
    {
        $acc = $this->makeAccount(AiProvider::Gemini, 'A1');
        $this->addModel($acc, 'gemini-2.0-flash', 'free');
        $this->addModel($acc, 'gemini-2.5-pro', 'paid');
        $this->addModel($acc, 'misterius-1', 'unknown');
        $this->routing(['free_policy' => 'free_only', 'allow_paid_fallback' => false]);
        $resolver = app(ModelResolver::class);
        $resolved = $resolver->resolve($this->user);
        $ids = array_map(fn ($c) => $c['model_id'], $resolved['candidates']);
        $this->assertContains('gemini-2.0-flash', $ids);
        $this->assertNotContains('gemini-2.5-pro', $ids);
        $this->assertNotContains('misterius-1', $ids);
    }

    /** 13. free-first tanpa izin paid tidak pakai berbayar. */
    public function test_free_first_without_paid_permission(): void
    {
        $acc = $this->makeAccount(AiProvider::Gemini, 'A1');
        $this->addModel($acc, 'gemini-2.0-flash', 'free');
        $this->addModel($acc, 'gemini-2.5-pro', 'paid');
        $this->routing(['free_policy' => 'free_first', 'allow_paid_fallback' => false]);
        $resolver = app(ModelResolver::class);
        $resolved = $resolver->resolve($this->user);
        $ids = array_map(fn ($c) => $c['model_id'], $resolved['candidates']);
        $this->assertContains('gemini-2.0-flash', $ids);
        $this->assertNotContains('gemini-2.5-pro', $ids);
    }

    /** 14. Manual tetap prioritas. */
    public function test_manual_priority_respected(): void
    {
        $acc = $this->makeAccount(AiProvider::Gemini, 'A1');
        $this->addModel($acc, 'gemini-2.0-flash', 'free');
        $this->addModel($acc, 'gemini-2.5-flash', 'free');
        $this->routing(['mode' => 'manual', 'primary_provider' => 'gemini', 'primary_model' => 'gemini-2.5-flash',
            'fallback_models' => [['provider' => 'gemini', 'model' => 'gemini-2.0-flash']], 'free_policy' => 'paid_allowed', 'allow_paid_fallback' => true]);
        $resolver = app(ModelResolver::class);
        $resolved = $resolver->resolve($this->user);
        $this->assertSame('gemini-2.5-flash', $resolved['candidates'][0]['model_id']);
        $this->assertSame('manual', $resolved['method']);
    }

    /** 15. Auto tidak mengubah preferensi manual tersimpan. */
    public function test_auto_does_not_mutate_manual(): void
    {
        $acc = $this->makeAccount(AiProvider::Gemini, 'A1');
        $this->addModel($acc, 'gemini-2.0-flash', 'free');
        $pref = $this->routing(['mode' => 'manual', 'primary_provider' => 'gemini', 'primary_model' => 'gemini-2.0-flash', 'free_policy' => 'paid_allowed', 'allow_paid_fallback' => true]);
        $resolver = app(ModelResolver::class);
        $resolver->resolve($this->user, $pref);
        $this->assertSame('manual', $pref->fresh()->mode);
        $this->assertSame('gemini-2.0-flash', $pref->fresh()->primary_model);
    }

    /** 16. Discovery gagal → katalog lama tetap dipakai. */
    public function test_discovery_failure_keeps_cache(): void
    {
        $acc = $this->makeAccount(AiProvider::Gemini, 'A1', 'key-aaaaaaaaaaaaaaaa');
        $this->addModel($acc, 'gemini-2.0-flash');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response('err', 503)]);
        $svc = app(ModelDiscoveryService::class);
        $result = $svc->discoverForAccount($acc->fresh(), true);
        $this->assertContains($result['source'], ['cache-stale', 'error']);
        $this->assertGreaterThanOrEqual(1, AiModelCatalog::where('account_id', $acc->id)->count());
    }

    /** 17. Dua user tidak campur katalog/status. */
    public function test_parallel_isolation(): void
    {
        $other = User::factory()->create();
        $a1 = $this->makeAccount(AiProvider::Gemini, 'A1');
        $this->addModel($a1, 'gemini-2.0-flash');
        $b1 = AiProviderAccount::create(['user_id' => $other->id, 'provider' => 'gemini', 'alias' => 'B1', 'api_key' => 'other-key-12345678', 'is_active' => true, 'is_valid' => true, 'priority' => 1, 'health_status' => 'unknown']);
        AiModelCatalog::create(['account_id' => $b1->id, 'user_id' => $other->id, 'provider' => 'gemini', 'model_id' => 'gemini-other', 'supports_generate' => true, 'category' => 'text_generation', 'free_tier' => 'free', 'is_eligible' => true, 'is_enabled' => true, 'discovery_source' => 'test', 'last_seen_at' => now()]);
        $this->routing();
        $resolver = app(ModelResolver::class);
        $mine = $resolver->resolve($this->user);
        $mineIds = array_map(fn ($c) => $c['model_id'], $mine['candidates']);
        $this->assertContains('gemini-2.0-flash', $mineIds);
        $this->assertNotContains('gemini-other', $mineIds);
    }

    /** 18. Retry tidak duplikat: satu pesan = satu hasil (executor tanpa efek bisnis). */
    public function test_no_duplicate_side_effects(): void
    {
        $acc = $this->makeAccount(AiProvider::Gemini, 'A1');
        $this->addModel($acc, 'm1');
        $this->addModel($acc, 'm2');
        $this->routing();
        $ex = app(AiRoutingExecutor::class);
        $sideEffects = 0;
        $out = $ex->executeParse($this->user, function () use (&$sideEffects) {
            $sideEffects++;
            if ($sideEffects === 1) {
                throw new AiProviderException('gemini', 'HTTP 500: server error');
            }

            return $this->okResult('gemini', 'm2');
        }, null, fn () => null);
        $this->assertSame(2, $sideEffects); // 1 gagal + 1 sukses, bukan N draft
        $this->assertSame('success', $out['routing']['final_status']);
    }

    /** 19. Normalizer tidak mengarang free-tier/model. */
    public function test_normalizer_no_fabrication(): void
    {
        $info = ModelNormalizer::normalizeGemini(['name' => 'models/gemini-9.9-flash-xyz', 'supportedGenerationMethods' => ['generateContent'], 'displayName' => 'X']);
        $this->assertTrue($info['eligible']);
        $this->assertSame('9.9', $info['generation']); // parse, bukan hardcode
        $unknown = ModelNormalizer::normalizeOpenAiCompatible('some-future-model-99', AiProvider::OpenAI);
        $this->assertSame('unknown', $unknown['free_tier']);
    }

    /** 20. Secret tidak bocor via safe array / log routing. */
    public function test_no_secret_leak(): void
    {
        $acc = $this->makeAccount(AiProvider::Gemini, 'Rahasia', 'super-secret-key-1234567890');
        $safe = $acc->fresh()->toSafeArray();
        $this->assertArrayNotHasKey('api_key', $safe);
        $this->assertStringNotContainsString('super-secret', json_encode($safe));
        $this->assertSame('7890', $safe['key_suffix']);
    }

    /** 21. Kandidat automatic disebar lintas akun (round-robin). */
    public function test_automatic_candidates_interleaved_across_accounts(): void
    {
        $a1 = $this->makeAccount(AiProvider::Gemini, 'A1', 'key-aaaaaaaaaaaaaaaa', 1);
        $a2 = $this->makeAccount(AiProvider::Gemini, 'A2', 'key-bbbbbbbbbbbbbbbb', 2);
        foreach (['gemini-2.0-flash', 'gemini-2.5-flash', 'gemini-3.0-flash'] as $model) {
            $this->addModel($a1, $model);
            $this->addModel($a2, $model.'-b');
        }
        $this->routing();
        $resolved = app(ModelResolver::class)->resolve($this->user);
        $accountIds = array_map(fn ($c) => $c['account']->id, array_slice($resolved['candidates'], 0, 4));
        $this->assertSame([$a1->id, $a2->id, $a1->id, $a2->id], $accountIds);
    }

    /** 22. Retry same-pair tidak menghabiskan jatah max_attempts. */
    public function test_retry_does_not_consume_candidate_budget(): void
    {
        $a1 = $this->makeAccount(AiProvider::Gemini, 'A1', 'key-aaaaaaaaaaaaaaaa', 1);
        $this->addModel($a1, 'gemini-2.0-flash');
        $this->routing(['max_attempts' => 1]);
        $calls = 0;
        $out = app(AiRoutingExecutor::class)->executeParse($this->user, function ($account, $model, $provider) use (&$calls) {
            $calls++;
            if ($calls === 1) {
                throw new AiProviderException('gemini', 'HTTP 503: provider overloaded');
            }

            return $this->okResult($provider->value, $model);
        }, null, fn () => null);

        $this->assertSame('success', $out['routing']['final_status']);
        $this->assertSame(2, $calls, 'Satu kandidat + satu retry, bukan dianggap dua kandidat');
        $this->assertSame(2, $out['routing']['attempt_count']);
    }

    /** 23. Akun yang masuk cooldown di-skip; akun lain tidak ikut menunggu. */
    public function test_account_cooldown_skips_remaining_models(): void
    {
        $a1 = $this->makeAccount(AiProvider::Gemini, 'A1', 'key-aaaaaaaaaaaaaaaa', 1);
        $a2 = $this->makeAccount(AiProvider::Gemini, 'A2', 'key-bbbbbbbbbbbbbbbb', 2);
        $this->addModel($a1, 'gemini-2.0-flash');
        $this->addModel($a1, 'gemini-2.5-flash');
        $this->addModel($a1, 'gemini-3.0-flash');
        $this->addModel($a2, 'gemini-2.5-flash');
        $this->routing(['max_attempts' => 4]);
        $calls = 0;
        $out = app(AiRoutingExecutor::class)->executeParse($this->user, function ($account, $model, $provider) use (&$calls, $a1) {
            $calls++;
            if ($account->id === $a1->id) {
                throw new AiProviderException('gemini', 'HTTP 503: provider overloaded');
            }

            return $this->okResult($provider->value, $model);
        }, null, fn () => null);

        $this->assertSame($a2->id, $out['routing']['selected_account_id']);
        $this->assertSame(3, $calls, 'Dua call untuk akun down + satu sukses di akun kedua');
        $this->assertNotNull($a1->fresh()->cooldown_until);
    }

    /** 24. Executor multi memakai distribusi + skip akun yang sama. */
    public function test_multi_executor_spreads_and_skips_failed_account(): void
    {
        $a1 = $this->makeAccount(AiProvider::Gemini, 'A1', 'key-aaaaaaaaaaaaaaaa', 1);
        $a2 = $this->makeAccount(AiProvider::Gemini, 'A2', 'key-bbbbbbbbbbbbbbbb', 2);
        $this->addModel($a1, 'gemini-2.0-flash');
        $this->addModel($a1, 'gemini-2.5-flash');
        $this->addModel($a2, 'gemini-2.5-flash');
        $this->routing(['max_attempts' => 3]);
        $calls = 0;
        $out = app(AiRoutingExecutor::class)->executeMulti($this->user, function ($account, $model, $provider) use (&$calls, $a1) {
            $calls++;
            if ($account->id === $a1->id) {
                throw new AiProviderException('gemini', 'HTTP 503: provider overloaded');
            }

            return new AIParseResultMulti(true, [], 0.9, null, false, null, [], $provider->value, $model);
        }, null, fn () => null);

        $this->assertSame($a2->id, $out['routing']['selected_account_id']);
        $this->assertSame(3, $calls);
    }

    /** 25. 503 asli diklasifikasikan SERVER, bukan TIMEOUT koneksi. */
    public function test_http_503_classified_as_server(): void
    {
        $server = AiErrorClassifier::classify(new AiTimeoutException('gemini', 503));
        $this->assertSame(AiErrorClassifier::CAT_SERVER, $server['category']);
        $this->assertSame(AiErrorClassifier::ACTION_RETRY_SAME, $server['action']);

        $timeout = AiErrorClassifier::classify(new AiTimeoutException('gemini'));
        $this->assertSame(AiErrorClassifier::CAT_TIMEOUT, $timeout['category']);
    }
}
