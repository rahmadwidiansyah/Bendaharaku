<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Enums\AiProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAiAccountRequest;
use App\Http\Requests\StoreAiRoutingRequest;
use App\Http\Requests\UpdateAiAccountRequest;
use App\Models\AiProviderAccount;
use App\Models\AiRoutingPreference;
use App\Services\AI\AiAccountManager;
use App\Services\AI\Routing\ModelResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiProviderAccountController extends Controller
{
    public function __construct(
        private AiAccountManager $accounts,
        private ModelResolver $resolver,
    ) {}

    /** Daftar akun aman (tanpa secret) + routing + preview kandidat. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $routing = $this->accounts->getOrCreateRouting($user);
        $resolved = $this->resolver->resolve($user, $routing);

        return response()->json([
            'accounts' => $this->accounts->listFor($user),
            'routing' => $routing,
            'candidates' => array_map(fn ($c) => [
                'provider' => $c['provider']->value ?? (string) $c['provider'],
                'model' => $c['model_id'],
                'account_id' => $c['account']->id,
                'account_alias' => $c['account']->alias ?? null,
                'source' => $c['source'] ?? null,
                'score' => $c['score'] ?? null,
            ], array_slice($resolved['candidates'], 0, 8)),
            'skipped' => array_slice($resolved['skipped'] ?? [], 0, 20),
            'method' => $resolved['method'] ?? 'unknown',
        ]);
    }

    public function store(StoreAiAccountRequest $request): JsonResponse
    {
        $user = $request->user();
        $provider = AiProvider::from($request->validated('provider'));
        // Cegah alias ganda per provider.
        $alias = trim((string) ($request->validated('alias') ?? '')) === '' ? 'Utama' : trim((string) $request->validated('alias'));
        $exists = AiProviderAccount::where('user_id', $user->id)
            ->where('provider', $provider->value)
            ->where('alias', $alias)
            ->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Alias sudah dipakai untuk provider ini. Gunakan alias lain.'], 422);
        }

        $account = $this->accounts->create(
            $user,
            $provider,
            $alias,
            (string) $request->validated('api_key'),
            $request->validated('base_url'),
            (int) ($request->validated('priority') ?? 1),
        );

        return response()->json(['success' => true, 'account' => $account->fresh()->toSafeArray()], 201);
    }

    public function update(UpdateAiAccountRequest $request, int $id): JsonResponse
    {
        $account = AiProviderAccount::where('user_id', $request->user()->id)->findOrFail($id);
        if ($request->has('alias')) {
            $newAlias = trim((string) $request->validated('alias'));
            if ($newAlias !== '' && $newAlias !== $account->alias) {
                $dup = AiProviderAccount::where('user_id', $account->user_id)
                    ->where('provider', $account->provider->value ?? $account->provider)
                    ->where('alias', $newAlias)
                    ->where('id', '!=', $account->id)
                    ->exists();
                if ($dup) {
                    return response()->json(['success' => false, 'message' => 'Alias sudah dipakai.'], 422);
                }
                $this->accounts->updateAlias($account, $newAlias);
            }
        }
        if ($request->filled('api_key')) {
            $this->accounts->replaceKey($account->fresh(), (string) $request->validated('api_key'), $request->validated('base_url'));
        } elseif ($request->has('base_url') && $account->provider === AiProvider::OpenAiCompatible) {
            $account->update(['base_url' => $request->validated('base_url')]);
        }
        if ($request->has('priority')) {
            $account->update(['priority' => max(1, (int) $request->validated('priority'))]);
        }
        if ($request->has('is_active')) {
            $this->accounts->setActive($account->fresh(), (bool) $request->validated('is_active'));
        }

        return response()->json(['success' => true, 'account' => $account->fresh()->toSafeArray()]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $account = AiProviderAccount::where('user_id', $request->user()->id)->findOrFail($id);
        try {
            $this->accounts->delete($request->user(), $account);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    /** Uji koneksi nyata per akun (tanpa membocorkan key). */
    public function test(Request $request, int $id): JsonResponse
    {
        $request->validate(['model' => ['nullable', 'string', 'max:150']]);
        $account = AiProviderAccount::where('user_id', $request->user()->id)->findOrFail($id);
        $result = $this->accounts->testConnection($account->fresh(), $request->input('model'));

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /** Refresh discovery katalog per akun (paksa live, fallback cache-stale). */
    public function refresh(Request $request, int $id): JsonResponse
    {
        $account = AiProviderAccount::where('user_id', $request->user()->id)->findOrFail($id);
        $result = $this->accounts->refreshDiscovery($account->fresh());

        return response()->json(['success' => $result['source'] === 'live' || $result['source'] === 'cache', 'discovery' => [
            'source' => $result['source'],
            'discovered' => $result['discovered'],
            'eligible' => $result['eligible'],
            'error' => $result['error'],
        ]]);
    }

    /** Model katalog per akun (aman, tanpa secret). */
    public function models(Request $request, int $id): JsonResponse
    {
        $account = AiProviderAccount::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json([
            'account' => $account->toSafeArray(),
            'models' => $account->models()->orderBy('model_id')->get()->map->toSafeArray(),
        ]);
    }

    /** Simpan preferensi routing (manual/auto, free policy, prioritas). */
    public function storeRouting(StoreAiRoutingRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $routing = AiRoutingPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'mode' => $data['mode'],
                'primary_provider' => $data['primary_provider'] ?? null,
                'primary_model' => $data['primary_model'] ?? null,
                'fallback_models' => $data['fallback_models'] ?? [],
                'provider_priority' => $data['provider_priority'] ?? AiRoutingPreference::defaultProviderPriority(),
                'free_policy' => $data['free_policy'],
                'allow_paid_fallback' => (bool) $data['allow_paid_fallback'],
                'max_candidates' => (int) ($data['max_candidates'] ?? 4),
                'max_attempts' => (int) ($data['max_attempts'] ?? 8),
                'expand_beyond_primary' => (bool) ($data['expand_beyond_primary'] ?? true),
            ]
        );

        // Validasi silang: mode manual wajib primary ada di katalog eligible (atau beri peringatan, jangan tolak keras
        // agar katalog kosong/discovery gagal tidak mengunci user).
        $warnings = [];
        if ($routing->mode === 'manual' && $routing->primary_model) {
            $resolved = $this->resolver->resolve($user, $routing->fresh());
            $found = false;
            foreach ($resolved['candidates'] as $c) {
                if ($c['model_id'] === $routing->primary_model) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $warnings[] = 'Model utama tidak ditemukan di kandidat layak saat ini ('.implode('; ', array_map(fn ($s) => ($s['model'] ?? '?').': '.($s['reason'] ?? ''), array_slice($resolved['skipped'] ?? [], 0, 3))).'). Sistem akan mencoba kandidat otomatis sebagai ekspansi.';
            }
        }

        return response()->json(['success' => true, 'routing' => $routing->fresh(), 'warnings' => $warnings]);
    }
}
