<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AiProvider;
use App\Models\AiModelCatalog;
use App\Models\AiProviderAccount;
use App\Models\AiRoutingPreference;
use App\Models\User;
use App\Models\UserAiCredential;
use App\Models\UserAiPreference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyAiPoolCommand extends Command
{
    protected $signature = 'ai:migrate-legacy-pool
        {--user= : Migrasi satu user saja (ID)}
        {--dry-run : Tampilkan rencana tanpa menulis}
        {--rollback : Hapus akun/katalog hasil migrasi (routing dibiarkan)}';

    protected $description = 'Migrasi satu-kali pengaturan AI lama (user_ai_credentials/preferences) ke pool (ai_provider_accounts + katalog + routing). Idempoten.';

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        $dryRun = (bool) $this->option('dry-run');
        $users = $this->option('user')
            ? User::whereKey($this->option('user'))->get()
            : User::whereHas('aiPreferences')->orWhereHas('aiCredentials')->get();

        if ($users->isEmpty()) {
            $this->info('Tidak ada user dengan pengaturan AI lama.');

            return self::SUCCESS;
        }

        foreach ($users as $user) {
            $this->migrateUser($user, $dryRun);
        }

        return self::SUCCESS;
    }

    private function migrateUser(User $user, bool $dryRun): void
    {
        $preferences = UserAiPreference::where('user_id', $user->id)->get()->keyBy(fn ($p) => $p->provider->value);
        $credentials = UserAiCredential::where('user_id', $user->id)->get()->keyBy(fn ($c) => $c->provider->value);

        $providers = $preferences->keys()->merge($credentials->keys())->unique()->values();
        $active = $preferences->first(fn ($p) => $p->is_active_provider);

        $created = 0;
        $skipped = 0;
        $fallbacks = [];

        foreach ($providers as $providerValue) {
            $provider = AiProvider::tryFrom($providerValue);
            if (! $provider) {
                $this->warn("User {$user->id}: provider '{$providerValue}' tidak dikenal, dilewati.");

                continue;
            }

            $already = AiProviderAccount::where('user_id', $user->id)
                ->where('provider', $provider->value)
                ->where('metadata->migrated_from', 'legacy')
                ->exists();
            if ($already) {
                $skipped++;
                $model = $preferences->get($providerValue)?->selected_model ?? $provider->defaultModel();
            } else {
                $model = $preferences->get($providerValue)?->selected_model ?? $provider->defaultModel();
                $isActiveLegacy = (bool) ($preferences->get($providerValue)?->is_active_provider ?? false);

                if (! $dryRun) {
                    DB::transaction(function () use ($user, $provider, $model, $isActiveLegacy, $credentials, $providerValue) {
                        $account = AiProviderAccount::create([
                            'user_id' => $user->id,
                            'provider' => $provider->value,
                            'alias' => ucfirst($provider->value).' (legacy)',
                            'api_key' => $credentials->get($providerValue)?->api_key ?? '',
                            'is_active' => true,
                            'is_valid' => (bool) ($credentials->get($providerValue)?->is_valid ?? true),
                            'priority' => $isActiveLegacy ? 1 : 5,
                            'health_status' => 'unknown',
                            'metadata' => ['migrated_from' => 'legacy'],
                        ]);

                        AiModelCatalog::create([
                            'account_id' => $account->id,
                            'user_id' => $user->id,
                            'provider' => $provider->value,
                            'model_id' => $model,
                            'display_name' => $model,
                            'supports_generate' => true,
                            'category' => 'text_generation',
                            'free_tier' => 'unknown',
                            'free_tier_source' => 'legacy_seed',
                            'is_eligible' => true,
                            'is_enabled' => true,
                            'discovery_source' => 'seed',
                            'last_seen_at' => now(),
                        ]);
                    });
                }
                $created++;
            }

            if (! ($active && $active->provider->value === $providerValue)) {
                $fallbacks[] = ['provider' => $providerValue, 'model' => $model];
            }
        }

        $routingExists = AiRoutingPreference::where('user_id', $user->id)->exists();
        if (! $routingExists && $active) {
            if ($dryRun) {
                $this->line("User {$user->id}: [dry-run] routing manual {$active->provider->value}/{$active->selected_model} + ".count($fallbacks).' fallback.');
            } else {
                AiRoutingPreference::create([
                    'user_id' => $user->id,
                    'mode' => 'manual',
                    'primary_provider' => $active->provider->value,
                    'primary_model' => $active->selected_model,
                    'fallback_models' => array_values($fallbacks),
                    'provider_priority' => AiRoutingPreference::defaultProviderPriority(),
                    'free_policy' => 'free_first',
                    'allow_paid_fallback' => false,
                    'max_candidates' => 4,
                    'max_attempts' => 8,
                    'expand_beyond_primary' => true,
                ]);
            }
        } elseif ($routingExists) {
            $this->line("User {$user->id}: routing sudah ada, tidak diubah.");
        }

        $this->info("User {$user->id}: {$created} akun dibuat, {$skipped} dilewati (sudah termigrasi).");
    }

    private function rollback(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $query = AiProviderAccount::where('metadata->migrated_from', 'legacy');
        if ($this->option('user')) {
            $query->where('user_id', $this->option('user'));
        }

        $count = (clone $query)->count();
        if ($dryRun) {
            $this->info("[dry-run] {$count} akun migrasi akan dihapus (beserta katalognya via cascade). Routing dibiarkan.");

            return self::SUCCESS;
        }

        $deleted = 0;
        $query->chunkById(100, function ($accounts) use (&$deleted) {
            foreach ($accounts as $account) {
                $account->delete();
                $deleted++;
            }
        });
        $this->info("Rollback selesai: {$deleted} akun migrasi dihapus. Routing dibiarkan apa adanya.");

        return self::SUCCESS;
    }
}
