<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\AiProvider;
use App\Models\User;
use App\Models\UserAiPreference;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AiPreferenceManager
{
    public function setModelPreference(
        User $user,
        AiProvider $provider,
        string $modelName,
    ): UserAiPreference {
        if (blank($modelName)) {
            throw new InvalidArgumentException('Nama model tidak boleh kosong.');
        }

        return UserAiPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider->value,
            ],
            [
                'selected_model' => $modelName,
            ],
        );
    }

    public function switchActiveProvider(User $user, AiProvider $provider): void
    {
        DB::transaction(function () use ($user, $provider): void {
            $user->aiPreferences()->update(['is_active_provider' => false]);

            $existing = UserAiPreference::where([
                'user_id' => $user->id,
                'provider' => $provider->value,
            ])->first();

            UserAiPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => $provider->value,
                ],
                [
                    'selected_model' => $existing?->selected_model ?? $provider->defaultModel(),
                    'is_active_provider' => true,
                ],
            );
        });
    }

    /**
     * Mendapatkan konfigurasi preferensi provider aktif milik pengguna.
     */
    public function getActivePreference(User $user): ?UserAiPreference
    {
        return $user->activeAiPreference;
    }

    /**
     * Mendapatkan preferensi default (OpenAI API Compatible dari .env) sebagai
     * model instance virtual yang tidak dipersistensikan.
     */
    public function getDefaultPreference(): ?UserAiPreference
    {
        $apiKey = (string) config('bendaharaku.ai.openai_compatible.api_key');
        $baseUrl = (string) config('bendaharaku.ai.openai_compatible.base_url');

        if (blank($apiKey) || blank($baseUrl)) {
            return null;
        }

        return new UserAiPreference([
            'provider' => AiProvider::OpenAiCompatible,
            'selected_model' => AiProvider::OpenAiCompatible->defaultModel(),
            'is_active_provider' => true,
        ]);
    }

    /**
     * Resolusi preferensi AI aktif: preferensi user jika ada, jika tidak
     * gunakan provider default (OpenAI API Compatible dari .env).
     */
    public function resolveActivePreference(User $user): ?UserAiPreference
    {
        return $this->getActivePreference($user) ?? $this->getDefaultPreference();
    }
}
