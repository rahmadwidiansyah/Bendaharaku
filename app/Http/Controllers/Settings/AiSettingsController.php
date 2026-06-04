<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Enums\AiProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveAiSettingsRequest;
use App\Models\UserAiCredential;
use App\Models\AiParseLog;
use App\Models\AiUsageLog;
use App\Services\AI\AiCredentialManager;
use App\Services\AI\AiPreferenceManager;
use App\Services\AI\PlaceholderConnectionTester;
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
                $isDraft = $log->is_success && $log->confidence < 0.80; // Sesuaikan dengan batas Confidence Guard
                
                return [
                    'id' => $log->id,
                    'provider' => strtoupper($log->provider),
                    'input_text' => $log->input_text,
                    'confidence' => round($log->confidence * 100), // Jadikan persentase (0-100)
                    'status' => $log->is_success ? ($isDraft ? 'Draft' : 'Executed') : 'Failed',
                    'error' => $log->error_message,
                    'date' => $log->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('Settings/Ai', [
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

        if ($request->filled('api_key')) {
            $this->credentialManager->setCredential($user, $provider, $request->validated('api_key'));
        }

        if ($request->boolean('is_active_provider')) {
            $this->preferenceManager->switchActiveProvider($user, $provider);
        }

        $this->preferenceManager->setModelPreference($user, $provider, $selectedModel);

        return redirect()
            ->back()
            ->with('success', 'Pengaturan AI untuk ' . strtoupper($provider->value) . ' berhasil diperbarui.');
    }

    public function testConnection(Request $request, PlaceholderConnectionTester $tester): JsonResponse
    {
        $request->validate([
            'provider' => ['required', 'string'],
            'api_key' => ['nullable', 'string'],
        ]);

        $provider = AiProvider::tryFrom((string) $request->input('provider'));

        if (!$provider) {
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
            AiProvider::Gemini => ['gemini-2.5-flash', 'gemini-2.5-pro'],
            AiProvider::OpenAI => ['gpt-5', 'gpt-5-mini'],
            AiProvider::DeepSeek => ['deepseek-chat'],
        };
    }
}