<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\AI\AiAccountManager;
use App\Services\AI\Routing\ModelResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiProviderStatusController extends Controller
{
    public function __construct(
        private readonly AiAccountManager $accounts,
        private readonly ModelResolver $resolver,
    ) {}

    /**
     * Halaman status provider (khusus tema M3).
     *
     * Menampilkan kesehatan akun provider, ringkasan routing, dan kandidat
     * berikutnya. Tema Default sudah memiliki informasi ini di halaman
     * Provider & Model, jadi pengguna Default diarahkan ke sana.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->ui_theme !== 'm3') {
            return redirect()->route('settings.ai.models');
        }

        $routing = $this->accounts->getOrCreateRouting($user);
        $resolved = $this->resolver->resolve($user, $routing);

        return Inertia::render('Material/Settings/AI/Status', [
            'accounts' => $this->accounts->listFor($user),
            'method' => $resolved['method'] ?? 'unknown',
            'candidates' => array_map(fn ($c) => [
                'provider' => $c['provider']->value ?? (string) $c['provider'],
                'model' => $c['model_id'],
                'account_alias' => $c['account']->alias ?? null,
                'source' => $c['source'] ?? null,
                'score' => $c['score'] ?? null,
            ], array_slice($resolved['candidates'], 0, 8)),
            'skipped' => array_slice($resolved['skipped'] ?? [], 0, 20),
        ]);
    }
}
