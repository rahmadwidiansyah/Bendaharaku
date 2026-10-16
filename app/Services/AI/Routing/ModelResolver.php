<?php

declare(strict_types=1);

namespace App\Services\AI\Routing;

use App\Enums\AiProvider;
use App\Models\AiModelCatalog;
use App\Models\AiProviderAccount;
use App\Models\AiRoutingPreference;
use App\Models\User;
use App\Services\AI\Discovery\ModelNormalizer;
use Illuminate\Support\Facades\Log;

/**
 * Menyusun candidate pool 3–4 model utama + ekspansi sesuai kebijakan.
 * Tidak memilih berdasarkan nama baru/cepat — hanya metadata terverifikasi.
 */
class ModelResolver
{
    public function __construct(
        private ProviderHealthTracker $health,
    ) {}

    /**
     * @return array{candidates:list<array{account:AiProviderAccount,model:AiModelCatalog|string,provider:AiProvider,model_id:string,source:string,score:float}>,skipped:list<array{provider:string,model:string,reason:string}>,method:string,primary_count:int,total_eligible:int}
     */
    public function resolve(User $user, ?AiRoutingPreference $routing = null): array
    {
        $routing ??= AiRoutingPreference::where('user_id', $user->id)->first();
        $mode = $routing?->mode ?? 'automatic';
        $maxCandidates = max(1, min(8, (int) ($routing?->max_candidates ?? 4)));
        $providerPriority = $routing?->providerPriority() ?? AiRoutingPreference::defaultProviderPriority();
        $freePolicy = (string) ($routing?->free_policy ?? 'free_first');
        $allowPaid = (bool) ($routing?->allow_paid_fallback ?? false);

        $accounts = AiProviderAccount::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->with('models')
            ->get();

        // Tambahkan akun virtual .env jika belum ada akun compatible.
        $virtual = $this->virtualEnvAccount($user, $accounts);
        if ($virtual) {
            $accounts->push($virtual);
        }

        $usable = $accounts->filter(fn ($a) => $this->health->isUsable($a))->values();
        if ($usable->isEmpty()) {
            // Semua cooldown/invalid: kembalikan alasan agar caller fallback lokal/legacy.
            $skipped = $accounts->map(fn ($a) => [
                'provider' => (string) ($a->provider->value ?? $a->provider),
                'model' => '*',
                'reason' => $a->isInCooldown() ? 'cooldown ('.($a->health_status ?? 'unknown').')' : 'akun nonaktif/tidak valid ('.($a->health_status ?? 'unknown').')',
            ])->all();

            return ['candidates' => [], 'skipped' => $skipped, 'method' => 'none_no_usable_account', 'primary_count' => 0, 'total_eligible' => 0];
        }

        if ($mode === 'manual' && ($routing?->primary_model || $routing?->fallbackList() !== [])) {
            return $this->resolveManual($usable, $routing, $providerPriority, $freePolicy, $allowPaid, $maxCandidates);
        }

        return $this->resolveAutomatic($usable, $providerPriority, $freePolicy, $allowPaid, $maxCandidates);
    }

    private function resolveManual($usableAccounts, AiRoutingPreference $routing, array $providerPriority, string $freePolicy, bool $allowPaid, int $maxCandidates): array
    {
        $wanted = [];
        if ($routing->primary_provider && $routing->primary_model) {
            $wanted[] = ['provider' => $routing->primary_provider, 'model' => $routing->primary_model, 'account_id' => null];
        }
        foreach ($routing->fallbackList() as $fb) {
            $wanted[] = $fb;
        }
        // Dedupe preservasi urutan.
        $seen = [];
        $wanted = array_values(array_filter($wanted, function ($w) use (&$seen) {
            $key = strtolower($w['provider'].'::'.$w['model'].'::'.($w['account_id'] ?? ''));
            if (isset($seen[$key])) {
                return false;
            }
            $seen[$key] = true;

            return true;
        }));

        $candidates = [];
        $skipped = [];
        foreach ($wanted as $w) {
            $provider = AiProvider::tryFrom((string) $w['provider']);
            if (! $provider) {
                $skipped[] = ['provider' => (string) $w['provider'], 'model' => (string) $w['model'], 'reason' => 'provider tidak dikenal'];

                continue;
            }
            $account = $this->findAccountFor($usableAccounts, $provider, (string) $w['model'], isset($w['account_id']) ? (int) $w['account_id'] : null);
            if (! $account) {
                $skipped[] = ['provider' => $provider->value, 'model' => (string) $w['model'], 'reason' => 'tidak ada akun aktif/sehat untuk provider ini'];

                continue;
            }
            $catalog = $this->findCatalog($account, (string) $w['model']);
            // Tanpa katalog sama sekali untuk akun → izinkan sebagai seed (backward compat) dengan skor netral.
            if (! $catalog && $account->models->isEmpty()) {
                if (! $this->passesFreePolicy('unknown', $freePolicy, $allowPaid)) {
                    $skipped[] = ['provider' => $provider->value, 'model' => (string) $w['model'], 'reason' => $this->freeSkipReason('unknown', $freePolicy)];

                    continue;
                }
                $candidates[] = ['account' => $account, 'model' => (string) $w['model'], 'provider' => $provider, 'model_id' => (string) $w['model'], 'source' => 'manual_seed', 'score' => 0.0];

                continue;
            }
            if (! $catalog) {
                $skipped[] = ['provider' => $provider->value, 'model' => (string) $w['model'], 'reason' => 'model tidak ada di katalog akun ini'];

                continue;
            }
            if (! $catalog->is_enabled || ! $catalog->is_eligible || ! $catalog->supports_generate) {
                $skipped[] = ['provider' => $provider->value, 'model' => (string) $w['model'], 'reason' => 'model tidak eligible/mendukung generate ('.($catalog->last_error ?? $catalog->category).')'];

                continue;
            }
            if (! $this->passesFreePolicy((string) $catalog->free_tier, $freePolicy, $allowPaid)) {
                $skipped[] = ['provider' => $provider->value, 'model' => (string) $w['model'], 'reason' => $this->freeSkipReason((string) $catalog->free_tier, $freePolicy)];

                continue;
            }
            $candidates[] = ['account' => $account, 'model' => $catalog, 'provider' => $provider, 'model_id' => $catalog->model_id, 'source' => 'manual', 'score' => 1000.0];
            if (count($candidates) >= $maxCandidates && ! ($routing->expand_beyond_primary ?? true)) {
                break;
            }
        }

        // Mode manual: JANGAN ubah diam-diam. Jika expand diizinkan dan kandidat < max,
        // lengkapi dari ranking otomatis TANPA mengubah preferensi tersimpan.
        if (($routing->expand_beyond_primary ?? true) && count($candidates) < $maxCandidates) {
            $auto = $this->resolveAutomatic($usableAccounts, $providerPriority, $freePolicy, $allowPaid, $maxCandidates);
            foreach ($auto['candidates'] as $c) {
                $dup = false;
                foreach ($candidates as $e) {
                    if ($e['account']->id === $c['account']->id && $e['model_id'] === $c['model_id']) {
                        $dup = true;
                        break;
                    }
                }
                if (! $dup) {
                    $c['source'] = 'auto_expand';
                    $candidates[] = $c;
                }
                if (count($candidates) >= max($maxCandidates, 4)) {
                    break;
                }
            }
            foreach ($auto['skipped'] as $s) {
                $skipped[] = $s;
            }
        }

        Log::info('ModelResolver: manual chain resolved', [
            'user_id' => $usableAccounts->first()?->user_id,
            'candidates' => array_map(fn ($c) => ($c['provider']->value ?? '').'/'.$c['model_id'].'#'.$c['account']->id, $candidates),
            'skipped' => $skipped,
        ]);

        return ['candidates' => array_values($candidates), 'skipped' => $skipped, 'method' => 'manual', 'primary_count' => min(count($candidates), $maxCandidates), 'total_eligible' => count($candidates)];
    }

    private function resolveAutomatic($usableAccounts, array $providerPriority, string $freePolicy, bool $allowPaid, int $maxCandidates): array
    {
        $freeOnly = $freePolicy === 'free_only';
        $rankIndex = array_flip($providerPriority);
        $rows = [];
        $skipped = [];
        $totalEligible = 0;

        foreach ($usableAccounts as $account) {
            $provider = $account->provider instanceof AiProvider ? $account->provider : AiProvider::tryFrom((string) $account->provider);
            if (! $provider) {
                continue;
            }
            // Tanpa katalog (akun baru/discovery gagal): seed dari default model agar tetap bisa dicoba sekali.
            if ($account->models->isEmpty()) {
                $defaultId = $provider->defaultModel();
                if (blank($defaultId)) {
                    $skipped[] = ['provider' => $provider->value, 'model' => '*', 'reason' => 'katalog kosong dan tidak ada default model'];

                    continue;
                }
                if (! $this->passesFreePolicy('unknown', $freePolicy, $allowPaid)) {
                    $skipped[] = ['provider' => $provider->value, 'model' => $defaultId, 'reason' => $this->freeSkipReason('unknown', $freePolicy)];

                    continue;
                }
                $rows[] = ['account' => $account, 'model' => $defaultId, 'provider' => $provider, 'model_id' => $defaultId, 'source' => 'seed_default', 'score' => $this->scoreSeed($providerPriority, $rankIndex, $provider, $account)];
                $totalEligible++;

                continue;
            }
            foreach ($account->models as $catalog) {
                if (! $catalog->is_enabled || ! $catalog->is_eligible || ! $catalog->supports_generate) {
                    continue;
                }
                $totalEligible++;
                if (! $this->passesFreePolicy((string) $catalog->free_tier, $freePolicy, $allowPaid)) {
                    $skipped[] = ['provider' => $provider->value, 'model' => $catalog->model_id, 'reason' => $this->freeSkipReason((string) $catalog->free_tier, $freePolicy)];

                    continue;
                }
                $info = [
                    'eligible' => true,
                    'release_channel' => $this->inferChannel($catalog),
                    'variant' => $this->inferVariant($catalog),
                    'generation' => $this->inferGeneration($catalog),
                    'free_tier' => (string) $catalog->free_tier,
                ];
                $score = ModelNormalizer::internalScore($info, $freeOnly);
                if ($score <= -1000) {
                    $skipped[] = ['provider' => $provider->value, 'model' => $catalog->model_id, 'reason' => $freeOnly ? 'bukan model gratis' : 'tidak layak'];

                    continue;
                }
                // Provider priority + account priority sebagai bobot utama, skor model sebagai tiebreak.
                $prioRank = $rankIndex[$provider->value] ?? 99;
                $score += (10 - min(10, $prioRank)) * 5.0;
                $score += max(0, 5 - (int) $account->priority) * 2.0;
                $score -= min(20, (int) $catalog->failure_count) * 1.0;
                $rows[] = ['account' => $account, 'model' => $catalog, 'provider' => $provider, 'model_id' => $catalog->model_id, 'source' => 'automatic', 'score' => $score];
            }
        }

        usort($rows, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return strcmp($a['model_id'], $b['model_id']);
            }

            return $b['score'] <=> $a['score'];
        });

        // free_first: gratis dulu, lalu berbayar/unknown hanya jika diizinkan — sudah difilter di passesFreePolicy.
        // free_first tanpa allow_paid == free_only efektif (kecuali unknown ditolak juga).
        $candidates = $rows; // ekspansi penuh; executor batasi via max_attempts.

        Log::info('ModelResolver: automatic chain resolved', [
            'user_id' => $usableAccounts->first()?->user_id,
            'free_policy' => $freePolicy,
            'allow_paid' => $allowPaid,
            'candidates' => array_map(fn ($c) => ($c['provider']->value ?? '').'/'.$c['model_id'].'#'.$c['account']->id, array_slice($candidates, 0, 8)),
            'skipped_count' => count($skipped),
        ]);

        return ['candidates' => array_values($candidates), 'skipped' => $skipped, 'method' => 'automatic', 'primary_count' => min(count($candidates), $maxCandidates), 'total_eligible' => $totalEligible];
    }

    private function passesFreePolicy(string $freeTier, string $freePolicy, bool $allowPaid): bool
    {
        $freeTier = strtolower($freeTier);

        return match ($freePolicy) {
            'free_only' => $freeTier === 'free',
            // free_first: gratis selalu boleh; non-gratis hanya jika paid fallback diizinkan.
            'free_first' => $freeTier === 'free' || $allowPaid,
            'paid_allowed' => true,
            default => $freeTier === 'free' || $allowPaid,
        };
    }

    private function freeSkipReason(string $freeTier, string $freePolicy): string
    {
        if ($freePolicy === 'free_only') {
            return $freeTier === 'unknown'
                ? 'status gratis tidak dapat diverifikasi (free-only menolak unknown)'
                : 'bukan model gratis (free-only)';
        }
        if ($freePolicy === 'free_first') {
            return 'model berbayar/unknown dilewati (free-first tanpa izin paid fallback)';
        }

        return 'difilter kebijakan gratis';
    }

    private function findAccountFor($usableAccounts, AiProvider $provider, string $modelId, ?int $accountId): ?AiProviderAccount
    {
        if ($accountId) {
            $found = $usableAccounts->firstWhere('id', $accountId);
            if ($found && ((string) ($found->provider->value ?? $found->provider)) === $provider->value) {
                return $found;
            }
        }
        // Pilih akun provider yang punya katalog model tsb (enabled), else akun prioritas terendah.
        $withModel = $usableAccounts
            ->filter(fn ($a) => ((string) ($a->provider->value ?? $a->provider)) === $provider->value)
            ->sortBy(fn ($a) => [$a->priority, $a->id]);
        foreach ($withModel as $account) {
            $has = $account->models->firstWhere('model_id', $modelId);
            if ($has) {
                return $account;
            }
        }

        return $withModel->first();
    }

    private function findCatalog(AiProviderAccount $account, string $modelId): ?AiModelCatalog
    {
        return $account->models->firstWhere('model_id', $modelId);
    }

    private function scoreSeed(array $providerPriority, array $rankIndex, AiProvider $provider, AiProviderAccount $account): float
    {
        $prioRank = $rankIndex[$provider->value] ?? 99;

        return 50.0 + (10 - min(10, $prioRank)) * 5.0 - (int) $account->priority;
    }

    private function inferChannel(AiModelCatalog $catalog): string
    {
        $lower = strtolower($catalog->model_id);
        if (str_contains($lower, 'experimental') || str_contains($lower, '-exp')) {
            return 'experimental';
        }
        if (str_contains($lower, 'preview')) {
            return 'preview';
        }
        if (str_ends_with($lower, '-latest')) {
            return 'latest_alias';
        }
        if (preg_match('/gemini-\d/i', $lower) || str_contains($lower, 'gpt') || str_contains($lower, 'deepseek')) {
            return 'stable';
        }

        return 'unknown';
    }

    private function inferVariant(AiModelCatalog $catalog): string
    {
        return ModelNormalizer::variant($catalog->model_id);
    }

    private function inferGeneration(AiModelCatalog $catalog): string
    {
        return ModelNormalizer::parseGeneration($catalog->model_id);
    }

    /** Akun virtual read-only dari .env agar instalasi lama tetap jalan tanpa migrasi manual. */
    private function virtualEnvAccount(User $user, $existingAccounts): ?AiProviderAccount
    {
        $hasCompatible = $existingAccounts->firstWhere(fn ($a) => ((string) ($a->provider->value ?? $a->provider)) === 'openai-compatible');
        if ($hasCompatible) {
            return null;
        }
        $apiKey = (string) config('bendaharaku.ai.openai_compatible.api_key');
        $baseUrl = (string) config('bendaharaku.ai.openai_compatible.base_url');
        $model = (string) config('bendaharaku.ai.openai_compatible.model', 'gpt-4o-mini');
        if (blank($apiKey) || blank($baseUrl)) {
            return null;
        }
        $account = new AiProviderAccount([
            'provider' => AiProvider::OpenAiCompatible,
            'alias' => 'Default (.env)',
            'is_active' => true,
            'priority' => 99,
            'health_status' => 'unknown',
        ]);
        $account->id = 0;
        $account->user_id = $user->id;
        $account->forceFill(['api_key' => $apiKey]);
        $account->base_url = $baseUrl;
        $account->is_valid = true;
        // Katalog virtual satu model default (seed) agar resolver punya kandidat.
        $catalog = new AiModelCatalog([
            'provider' => AiProvider::OpenAiCompatible,
            'model_id' => $model !== '' ? $model : 'gpt-4o-mini',
            'display_name' => $model !== '' ? $model : 'gpt-4o-mini',
            'supports_generate' => true,
            'category' => 'text_generation',
            'free_tier' => 'unknown',
            'free_tier_source' => 'env_default',
            'is_eligible' => true,
            'is_enabled' => true,
            'discovery_source' => 'seed',
        ]);
        $catalog->account_id = 0;
        $catalog->user_id = $user->id;
        $account->setRelation('models', collect([$catalog]));

        return $account;
    }
}
