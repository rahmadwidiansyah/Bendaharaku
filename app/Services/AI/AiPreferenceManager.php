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
            UserAiPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => $provider->value,
                ],
                [
                    'selected_model' => $provider->defaultModel(),
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
}
