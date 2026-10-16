<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AiProvider;
use App\Models\AiModelCatalog;
use App\Models\AiProviderAccount;
use App\Models\AiRoutingPreference;
use App\Models\User;
use App\Models\UserAiCredential;
use App\Models\UserAiPreference;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrateLegacyAiPoolTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    private function seedLegacy(): void
    {
        UserAiPreference::create([
            'user_id' => $this->user->id, 'provider' => AiProvider::Gemini,
            'selected_model' => 'gemini-2.0-flash', 'is_active_provider' => true,
        ]);
        UserAiCredential::create([
            'user_id' => $this->user->id, 'provider' => AiProvider::Gemini,
            'api_key' => 'test-key-gemini', 'is_valid' => true,
        ]);
        UserAiPreference::create([
            'user_id' => $this->user->id, 'provider' => AiProvider::OpenAI,
            'selected_model' => 'gpt-4o-mini', 'is_active_provider' => false,
        ]);
        UserAiCredential::create([
            'user_id' => $this->user->id, 'provider' => AiProvider::OpenAI,
            'api_key' => 'test-key-openai', 'is_valid' => true,
        ]);
    }

    public function test_migrates_accounts_catalog_and_routing(): void
    {
        $this->seedLegacy();

        $this->artisan('ai:migrate-legacy-pool', ['--user' => $this->user->id])
            ->assertSuccessful();

        $accounts = AiProviderAccount::where('user_id', $this->user->id)->get();
        $this->assertCount(2, $accounts);

        $gemini = $accounts->firstWhere('provider.value', AiProvider::Gemini->value);
        $this->assertTrue($gemini->is_active);
        $this->assertEquals(1, $gemini->priority);
        $this->assertEquals('test-key-gemini', $gemini->api_key);

        $catalog = AiModelCatalog::where('account_id', $gemini->id)->first();
        $this->assertEquals('gemini-2.0-flash', $catalog->model_id);
        $this->assertTrue($catalog->is_eligible && $catalog->is_enabled);

        $routing = AiRoutingPreference::where('user_id', $this->user->id)->first();
        $this->assertEquals('manual', $routing->mode);
        $this->assertEquals('gemini', $routing->primary_provider);
        $this->assertEquals('gemini-2.0-flash', $routing->primary_model);
        $this->assertEquals(
            [['provider' => 'openai', 'model' => 'gpt-4o-mini']],
            $routing->fallbackList()
        );
    }

    public function test_idempotent_and_dry_run(): void
    {
        $this->seedLegacy();

        $this->artisan('ai:migrate-legacy-pool', ['--user' => $this->user->id])->assertSuccessful();
        $this->artisan('ai:migrate-legacy-pool', ['--user' => $this->user->id, '--dry-run' => true])->assertSuccessful();

        $this->assertEquals(2, AiProviderAccount::where('user_id', $this->user->id)->count());
        $this->assertEquals(1, AiRoutingPreference::where('user_id', $this->user->id)->count());
    }

    public function test_rollback_removes_only_migrated_accounts(): void
    {
        $this->seedLegacy();
        $this->artisan('ai:migrate-legacy-pool', ['--user' => $this->user->id])->assertSuccessful();

        // Akun buatan user manual tidak boleh ikut terhapus.
        $manual = AiProviderAccount::create([
            'user_id' => $this->user->id, 'provider' => AiProvider::DeepSeek,
            'alias' => 'Manual', 'api_key' => 'k', 'is_active' => true,
            'is_valid' => true, 'priority' => 2, 'health_status' => 'unknown',
        ]);

        $this->artisan('ai:migrate-legacy-pool', ['--rollback' => true, '--user' => $this->user->id])
            ->assertSuccessful();

        $this->assertEquals(0, AiProviderAccount::where('user_id', $this->user->id)
            ->where('metadata->migrated_from', 'legacy')->count());
        $this->assertDatabaseHas('ai_provider_accounts', ['id' => $manual->id]);
        // Routing dibiarkan (tercatat di laporan).
        $this->assertEquals(1, AiRoutingPreference::where('user_id', $this->user->id)->count());
    }

    public function test_no_legacy_rows_migrates_nothing(): void
    {
        $this->artisan('ai:migrate-legacy-pool', ['--user' => $this->user->id])->assertSuccessful();

        $this->assertEquals(0, AiProviderAccount::where('user_id', $this->user->id)->count());
        $this->assertEquals(0, AiRoutingPreference::where('user_id', $this->user->id)->count());
    }
}
