<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Enums\AiProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveAiSettingsRequest;
use App\Models\AiParseLog;
use App\Models\AiUsageLog;
use App\Models\UserAiCredential;
use App\Services\AI\AiCredentialManager;
use App\Services\AI\AiPreferenceManager;
use App\Services\AI\PlaceholderConnectionTester;
use App\Support\SettingsChangeLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AiSettingsController extends Controller
{
    public function __construct(
        private AiCredentialManager $credentialManager,
        private AiPreferenceManager $preferenceManager,
    ) {}

    public function index(): Response
    {
        $user = Auth::user()->load(['aiCredentials', 'aiPreferences']);

        $providerStatuses = [];
        $modelsByProvider = [];

        foreach (AiProvider::cases() as $provider) {
            $credential = $user->aiCredentials->where('provider', $provider)->first();
            $preference = $user->aiPreferences->where('provider', $provider)->first();

            $modelsByProvider[$provider->value] = $this->modelsFor($provider);

            $providerStatuses[$provider->value] = [
                'status' => $credential instanceof UserAiCredential
                    ? ($credential->is_valid ? 'Connected' : 'Invalid')
                    : 'Not Configured',
                'selected_model' => $preference?->selected_model ?? $provider->defaultModel(),
                'is_active_provider' => $preference?->is_active_provider ?? false,
            ];
        }

        // 1. Ambil Statistik Penggunaan Token (Di-group per provider)
        $usageStats = AiUsageLog::where('user_id', $user->id)
            ->selectRaw('provider, SUM(prompt_tokens) as total_prompt, SUM(completion_tokens) as total_completion, SUM(total_tokens) as total_used')
            ->groupBy('provider')
            ->get()
            ->keyBy('provider');

        // 2. Ambil 10 Riwayat Log Terakhir
        $recentLogs = AiParseLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                // Gunakan kolom status langsung dari database
                // status bisa: 'draft', 'executed', 'failed', 'rate_limit', 'timeout', dll.
                $rawStatus = strtolower((string) ($log->status ?? ''));

                $status = match (true) {
                    $rawStatus === 'draft' => 'Draft',
                    in_array($rawStatus, ['executed', 'success', ''])
                        && $log->is_success => 'Executed',
                    str_contains($rawStatus, 'rate') || str_contains($rawStatus, 'quota') => 'Rate Limit',
                    str_contains($rawStatus, 'timeout') => 'Timeout',
                    ! $log->is_success => 'Failed',
                    default => ucfirst($rawStatus) ?: 'Executed',
                };

                return [
                    'id' => $log->id,
                    'provider' => strtoupper($log->provider ?? 'PYTHON-NLP'),
                    'input_text' => $log->input_text,
                    'confidence' => $log->final_confidence ? round($log->final_confidence * 100) : null,
                    'status' => $status,
                    'error' => $log->error_message,
                    'date' => $log->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('Settings/AI/Models', [
            'providerStatuses' => $providerStatuses,
            'availableProviders' => array_map(
                static fn (AiProvider $provider): string => $provider->value,
                AiProvider::cases(),
            ),
            'modelsByProvider' => $modelsByProvider,
            'usageStats' => $usageStats,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function store(SaveAiSettingsRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $provider = AiProvider::from($request->validated('provider'));
        $selectedModel = $request->validated('selected_model');

        // snapshot old values
        $oldPreference = $user->aiPreferences()->where('provider', $provider->value)->first();
        $oldModel = $oldPreference?->selected_model ?? null;
        $oldActive = $oldPreference?->is_active_provider ?? false;
        $oldCredential = $user->aiCredentials()->where('provider', $provider->value)->first();
        $hadCredential = $oldCredential instanceof UserAiCredential;

        if ($request->filled('api_key')) {
            $this->credentialManager->setCredential($user, $provider, $request->validated('api_key'));

            // log that api key/credential was updated (do not store the key itself)
            SettingsChangeLogger::logChange(
                $user,
                'ai_credentials',
                'settings.ai.providers',
                $hadCredential ? 'exists' : null,
                'updated'
            );
        }

        // Jadikan aktif jika: user centang checkbox ATAU belum ada provider aktif sama sekali
        $hasNoActiveProvider = ! $user->aiPreferences()->where('is_active_provider', true)->exists();
        if ($request->boolean('is_active_provider') || $hasNoActiveProvider) {
            $this->preferenceManager->switchActiveProvider($user, $provider);

            // log active provider switch
            SettingsChangeLogger::logChange(
                $user,
                'is_active_provider',
                'settings.ai.providers',
                $oldActive ? 'true' : 'false',
                'true'
            );
        }

        $this->preferenceManager->setModelPreference($user, $provider, $selectedModel);

        // log model change if differs
        if ($oldModel !== $selectedModel) {
            SettingsChangeLogger::logChange(
                $user,
                'selected_model',
                'settings.ai.providers',
                $oldModel,
                $selectedModel
            );
        }

        return redirect()
            ->back()
            ->with('success', 'Pengaturan AI untuk '.strtoupper($provider->value).' berhasil diperbarui.');
    }

    public function testConnection(Request $request, PlaceholderConnectionTester $tester): JsonResponse
    {
        $request->validate([
            'provider' => ['required', 'string'],
            'api_key' => ['nullable', 'string'],
        ]);

        $provider = AiProvider::tryFrom((string) $request->input('provider'));

        if (! $provider) {
            return response()->json([
                'success' => false,
                'message' => 'Provider tidak terdaftar di sistem.',
            ], 422);
        }

        $user = Auth::user();
        $storedCredential = $user->aiCredentials()
            ->where('provider', $provider->value)
            ->first();

        $apiKey = (string) $request->input('api_key', '');

        if (blank($apiKey)) {
            $apiKey = $storedCredential?->api_key ?? '';
        }

        if (blank($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Token API tidak ditemukan untuk dilakukan pengujian.',
            ], 422);
        }

        $result = $tester->test($provider, $apiKey);

        if ($result['success'] && $storedCredential) {
            $storedCredential->update(['is_valid' => true]);
        }

        return response()->json($result);
    }

    /**
     * @return list<string>
     */
    private function modelsFor(AiProvider $provider): array
    {
        return match ($provider) {
            AiProvider::Gemini => [
                // Gemini 3.5 (Terbaru)
                'gemini-3.5-flash',
                // Gemini 3.1
                'gemini-3.1-pro-preview',
                'gemini-3.1-flash-lite',
                'gemini-3.1-flash-image',
                // Gemini 3.0
                'gemini-3-flash-preview',
                'gemini-3-pro-image',
                // Gemini 2.5
                'gemini-2.5-pro',
                'gemini-2.5-flash',
                'gemini-2.5-flash-lite',
                // Gemini 2.0
                'gemini-2.0-flash',
                'gemini-2.0-flash-lite',
                // Gemini 1.5
                'gemini-1.5-flash',
                'gemini-1.5-flash-8b',
                'gemini-1.5-pro',
            ],
            AiProvider::OpenAI => ['gpt-4o-mini', 'gpt-4o'],
            AiProvider::DeepSeek => ['deepseek-chat'],
        };
    }
}
