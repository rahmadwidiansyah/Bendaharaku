<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Multi-account per provider (BYOK, banyak key per provider) ──
        Schema::create('ai_provider_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50); // gemini|openai|deepseek|openai-compatible
            $table->string('alias', 100)->default('Utama');
            $table->text('api_key'); // cast encrypted di model
            $table->text('base_url')->nullable(); // khusus openai-compatible per akun
            $table->boolean('is_active')->default(true);
            $table->boolean('is_valid')->default(true);
            $table->integer('priority')->default(1);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status', 50)->nullable(); // ok|auth_error|transient|invalid|unknown
            $table->text('last_test_message')->nullable();
            $table->timestamp('last_discovery_at')->nullable();
            $table->string('discovery_status', 50)->nullable(); // live|cache|cache-stale|error|manual
            $table->integer('consecutive_failures')->default(0);
            $table->timestamp('cooldown_until')->nullable();
            $table->string('health_status', 20)->default('unknown'); // healthy|degraded|rate_limited|unavailable|invalid|disabled|unknown
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'alias']);
            $table->index(['user_id', 'provider', 'is_active']);
            $table->index(['user_id', 'health_status']);
        });

        // ── Katalog model per akun (hasil discovery, per-key scoping) ──
        Schema::create('ai_model_catalog', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained('ai_provider_accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('model_id', 150);
            $table->string('display_name', 200)->nullable();
            $table->boolean('supports_generate')->default(false);
            $table->string('category', 50)->default('other'); // text_generation|multimodal|embedding|image_generation|...|other
            $table->string('free_tier', 20)->default('unknown'); // free|paid|unknown
            $table->string('free_tier_source', 50)->default('local_policy');
            $table->integer('input_token_limit')->nullable();
            $table->integer('output_token_limit')->nullable();
            $table->boolean('is_eligible')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->string('discovery_source', 20)->default('live'); // live|manual|seed
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->integer('failure_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'model_id']);
            $table->index(['user_id', 'provider', 'is_eligible']);
            $table->index(['account_id', 'is_eligible', 'is_enabled']);
        });

        // ── Preferensi routing per user (candidate pool + kebijakan) ──
        Schema::create('ai_routing_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mode', 20)->default('automatic'); // manual|automatic
            $table->string('primary_provider', 50)->nullable();
            $table->string('primary_model', 150)->nullable();
            $table->json('fallback_models')->nullable(); // [{provider, model, account_id?}]
            $table->json('provider_priority')->nullable(); // ['gemini','openai','deepseek','openai-compatible']
            $table->string('free_policy', 20)->default('free_first'); // free_only|free_first|paid_allowed
            $table->boolean('allow_paid_fallback')->default(false);
            $table->integer('max_candidates')->default(4);
            $table->integer('max_attempts')->default(8);
            $table->boolean('expand_beyond_primary')->default(true);
            $table->timestamps();
        });

        // ── Migrasi data lama → akun baru (aman, tidak hapus tabel lama) ──
        $this->migrateLegacyData();
    }

    private function migrateLegacyData(): void
    {
        // Setiap credential lama → satu akun "Utama" per (user, provider).
        // Setiap preference lama → seed katalog manual + routing awal.
        try {
            $credentials = DB::table('user_ai_credentials')->get();
            foreach ($credentials as $cred) {
                $exists = DB::table('ai_provider_accounts')
                    ->where('user_id', $cred->user_id)
                    ->where('provider', $cred->provider)
                    ->where('alias', 'Utama')
                    ->exists();
                if ($exists) {
                    continue;
                }

                $isActivePref = DB::table('user_ai_preferences')
                    ->where('user_id', $cred->user_id)
                    ->where('provider', $cred->provider)
                    ->value('is_active_provider');

                // Jika tak ada preference aktif sama sekali dan ini openai-compatible,
                // tandai aktif agar perilaku default .env tetap terjaga.
                if ($isActivePref === null) {
                    $hasAnyActive = DB::table('user_ai_preferences')
                        ->where('user_id', $cred->user_id)
                        ->where('is_active_provider', true)
                        ->exists();
                    $isActivePref = ($cred->provider === 'openai-compatible' && ! $hasAnyActive);
                }

                $accountId = DB::table('ai_provider_accounts')->insertGetId([
                    'user_id' => $cred->user_id,
                    'provider' => $cred->provider,
                    'alias' => 'Utama',
                    'api_key' => $cred->api_key, // sudah terenkripsi di app; simpan apa adanya
                    'is_active' => true,
                    'is_valid' => (bool) ($cred->is_valid ?? true),
                    'priority' => 1,
                    'health_status' => ($cred->is_valid ?? true) ? 'unknown' : 'invalid',
                    'discovery_status' => 'manual',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Seed katalog dari preference lama (manual, eligible) agar routing langsung punya kandidat.
                $pref = DB::table('user_ai_preferences')
                    ->where('user_id', $cred->user_id)
                    ->where('provider', $cred->provider)
                    ->first();
                if ($pref && ! blank($pref->selected_model ?? null)) {
                    DB::table('ai_model_catalog')->insert([
                        'account_id' => $accountId,
                        'user_id' => $cred->user_id,
                        'provider' => $cred->provider,
                        'model_id' => $pref->selected_model,
                        'display_name' => $pref->selected_model,
                        'supports_generate' => true,
                        'category' => 'text_generation',
                        'free_tier' => 'unknown',
                        'free_tier_source' => 'legacy_seed',
                        'is_eligible' => true,
                        'is_enabled' => true,
                        'discovery_source' => 'seed',
                        'last_seen_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Buat routing default per user yang punya credential/preference.
            $userIds = DB::table('user_ai_credentials')->distinct()->pluck('user_id')
                ->merge(DB::table('user_ai_preferences')->distinct()->pluck('user_id'))
                ->unique();
            foreach ($userIds as $userId) {
                $exists = DB::table('ai_routing_preferences')->where('user_id', $userId)->exists();
                if ($exists) {
                    continue;
                }
                $active = DB::table('user_ai_preferences')
                    ->where('user_id', $userId)
                    ->where('is_active_provider', true)
                    ->first();
                DB::table('ai_routing_preferences')->insert([
                    'user_id' => $userId,
                    // Pertahankan perilaku aman: jika user sudah pilih aktif → manual (tidak diubah diam-diam).
                    // Jika belum ada → automatic agar discovery bisa menyusun kandidat.
                    'mode' => $active ? 'manual' : 'automatic',
                    'primary_provider' => $active?->provider,
                    'primary_model' => $active?->selected_model,
                    'fallback_models' => json_encode([]),
                    'provider_priority' => json_encode(['gemini', 'openai', 'deepseek', 'openai-compatible']),
                    'free_policy' => 'free_first',
                    'allow_paid_fallback' => false,
                    'max_candidates' => 4,
                    'max_attempts' => 8,
                    'expand_beyond_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Akun virtual dari .env (openai-compatible tanpa credential DB) TIDAK dibuat di sini;
            // resolver akan membuatnya secara lazy sebagai akun virtual read-only (lihat AiAccountManager).
        } catch (Throwable $e) {
            // Migrasi data tidak boleh menggagalkan pembuatan tabel.
            report($e);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_routing_preferences');
        Schema::dropIfExists('ai_model_catalog');
        Schema::dropIfExists('ai_provider_accounts');
    }
};
