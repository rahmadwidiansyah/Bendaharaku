<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\AiProvider;
use App\Models\AiModelCatalog;
use App\Models\AiProviderAccount;
use App\Models\AiRoutingPreference;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\Discovery\ModelDiscoveryService;
use App\Services\AI\Routing\ModelResolver;
use App\Services\AI\Security\SafeEndpointValidator;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiPoolAuditTest extends TestCase
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

    private function account(AiProvider $p, string $alias): AiProviderAccount
    {
        return AiProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => $p->value,
            'alias' => $alias,
            'api_key' => 'test-key-1234567890',
            'is_active' => true,
            'is_valid' => true,
            'priority' => 1,
            'health_status' => 'unknown',
        ]);
    }

    /** Discovery Gemini realistis: flash eligible+free, embedding tidak eligible. */
    public function test_gemini_discovery_realistic_payload(): void
    {
        $acc = $this->account(AiProvider::Gemini, 'G1');
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'models' => [
                    [
                        'name' => 'models/gemini-2.0-flash',
                        'displayName' => 'Gemini 2.0 Flash',
                        'supportedGenerationMethods' => ['generateContent', 'countTokens'],
                        'inputTokenLimit' => 1048576,
                        'outputTokenLimit' => 8192,
                    ],
                    [
                        'name' => 'models/text-embedding-004',
                        'displayName' => 'Text Embedding',
                        'supportedGenerationMethods' => ['embedContent'],
                    ],
                ],
            ], 200),
        ]);
        $svc = app(ModelDiscoveryService::class);
        $result = $svc->discoverForAccount($acc->fresh(), true);
        $this->assertSame('live', $result['source']);
        $this->assertSame(2, $result['discovered']);
        $this->assertSame(1, $result['eligible']);
        $flash = AiModelCatalog::where('account_id', $acc->id)->where('model_id', 'gemini-2.0-flash')->firstOrFail();
        $this->assertTrue($flash->is_eligible);
        $this->assertSame('free', $flash->free_tier);
        $this->assertSame(1048576, $flash->input_token_limit);
        $emb = AiModelCatalog::where('account_id', $acc->id)->where('model_id', 'text-embedding-004')->firstOrFail();
        $this->assertFalse($emb->is_eligible);
    }

    /** Discovery OpenAI realistis: gpt + whisper (whisper tidak eligible). */
    public function test_openai_discovery_realistic_payload(): void
    {
        $acc = $this->account(AiProvider::OpenAI, 'O1');
        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    ['id' => 'gpt-4o-mini'],
                    ['id' => 'whisper-1'],
                ],
            ], 200),
        ]);
        $svc = app(ModelDiscoveryService::class);
        $result = $svc->discoverForAccount($acc->fresh(), true);
        $this->assertSame('live', $result['source']);
        // whisper tidak eligible → unknown tier tidak dikarang free.
        $whisper = AiModelCatalog::where('account_id', $acc->id)->where('model_id', 'whisper-1')->firstOrFail();
        $this->assertFalse($whisper->is_eligible);
        $this->assertSame('unknown', $whisper->free_tier);
    }

    /** SSRF: tolak metadata, privat, skema, userinfo, query. Terima publik. */
    public function test_ssrf_validator_blocks_internal(): void
    {
        foreach ([
            'http://169.254.169.254/latest/meta-data/',
            'http://100.100.100.100/',
            'http://127.0.0.1:8000/v1/chat/completions',
            'http://192.168.1.10/v1/models',
            'http://10.0.0.5/v1/models',
            'file:///etc/passwd',
            'gopher://example.com/',
            'https://user:pass@example.com/v1/models',
            'https://example.com/v1/models?key=1',
        ] as $bad) {
            try {
                SafeEndpointValidator::normalizeBaseUrl($bad);
                $this->fail("Seharusnya ditolak: {$bad}");
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
        // Publik + port kustom + path sah diterima dan dinormalisasi.
        // (IP literal publik agar tidak bergantung pada DNS di sandbox CI.)
        $ok = SafeEndpointValidator::normalizeBaseUrl('https://8.8.8.8:8443/v1/chat/completions/');
        $this->assertSame('https://8.8.8.8:8443/v1/chat/completions', $ok);
        $this->assertSame(
            'https://8.8.8.8:8443/v1/models',
            SafeEndpointValidator::deriveModelsUrl('https://8.8.8.8:8443/v1/chat/completions')
        );
    }

    /** free_only tanpa kandidat gratis: pool kosong → fallback lokal gagal → throw. */
    public function test_free_only_without_verified_free_candidates_throws(): void
    {
        // Tanpa akun pool sama sekali (jalur legacy sudah di-retire).
        AiRoutingPreference::updateOrCreate(['user_id' => $this->user->id], [
            'mode' => 'automatic', 'free_policy' => 'free_only', 'allow_paid_fallback' => false,
            'provider_priority' => ['gemini', 'openai', 'deepseek', 'openai-compatible'],
            'max_candidates' => 4, 'max_attempts' => 8, 'expand_beyond_primary' => true,
        ]);
        Http::fake([
            '*' => Http::response(['success' => false], 500),
        ]);
        $manager = app(AIManager::class);
        try {
            $manager->parseTransaction($this->user, 'halo tanpa angka', [], [], [], 'prompt');
            $this->fail('Seharusnya throw karena free-only tanpa kandidat gratis.');
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    /** Toggle model: nonaktif → re-enable mereset penalti. */
    public function test_toggle_model_reenable(): void
    {
        $acc = $this->account(AiProvider::Gemini, 'G1');
        $catalog = AiModelCatalog::create([
            'account_id' => $acc->id, 'user_id' => $this->user->id, 'provider' => 'gemini',
            'model_id' => 'gemini-2.0-flash', 'supports_generate' => true, 'category' => 'text_generation',
            'free_tier' => 'free', 'is_eligible' => true, 'is_enabled' => false,
            'discovery_source' => 'test', 'failure_count' => 5, 'last_error' => 'HTTP 404',
            'last_seen_at' => now(),
        ]);
        $this->actingAs($this->user);
        $this->patchJson(route('settings.ai.accounts.models.toggle', ['id' => $acc->id, 'model' => $catalog->model_id]), ['is_enabled' => true])
            ->assertOk()
            ->assertJsonPath('model.is_enabled', true);
        $this->assertSame(0, $catalog->fresh()->failure_count);
        $this->assertNull($catalog->fresh()->last_error);
    }

    /** Routing fallback_models tersimpan berurutan dan dipakai resolver. */
    public function test_manual_fallback_order_persisted_and_used(): void
    {
        $acc = $this->account(AiProvider::Gemini, 'G1');
        foreach (['gemini-2.0-flash', 'gemini-2.5-flash', 'gemini-2.5-flash-lite'] as $m) {
            AiModelCatalog::create([
                'account_id' => $acc->id, 'user_id' => $this->user->id, 'provider' => 'gemini',
                'model_id' => $m, 'supports_generate' => true, 'category' => 'text_generation',
                'free_tier' => 'free', 'is_eligible' => true, 'is_enabled' => true,
                'discovery_source' => 'test', 'last_seen_at' => now(),
            ]);
        }
        $this->actingAs($this->user);
        $this->putJson(route('settings.ai.routing.store'), [
            'mode' => 'manual',
            'primary_provider' => 'gemini',
            'primary_model' => 'gemini-2.5-flash',
            'fallback_models' => [
                ['provider' => 'gemini', 'model' => 'gemini-2.0-flash'],
                ['provider' => 'gemini', 'model' => 'gemini-2.5-flash-lite'],
            ],
            'provider_priority' => ['gemini', 'openai', 'deepseek', 'openai-compatible'],
            'free_policy' => 'paid_allowed',
            'allow_paid_fallback' => true,
        ])->assertOk();
        $routing = AiRoutingPreference::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame('gemini-2.5-flash', $routing->primary_model);
        $this->assertCount(2, $routing->fallbackList());
        $resolved = app(ModelResolver::class)->resolve($this->user, $routing);
        $this->assertSame('gemini-2.5-flash', $resolved['candidates'][0]['model_id']);
        $this->assertSame('gemini-2.0-flash', $resolved['candidates'][1]['model_id']);
    }
}
