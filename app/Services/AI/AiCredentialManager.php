<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\AiProvider;
use App\Models\User;
use App\Models\UserAiCredential;
use InvalidArgumentException;

class AiCredentialManager
{
    /**
     * @param array<string, mixed> $meta
     */
    public function setCredential(
        User $user,
        AiProvider $provider,
        string $apiKey,
        array $meta = [],
    ): UserAiCredential {
        if (blank($apiKey)) {
            throw new InvalidArgumentException('API Key tidak boleh kosong.');
        }

        return UserAiCredential::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider->value,
            ],
            [
                'api_key' => $apiKey,
                'meta' => $meta === [] ? null : $meta,
                'is_valid' => true,
            ],
        );
    }

    public function getCredential(User $user, AiProvider $provider): ?UserAiCredential
    {
        return $user->aiCredentials()
            ->where('provider', $provider->value)
            ->where('is_valid', true)
            ->first();
    }

    public function markAsInvalid(UserAiCredential $credential): void
    {
        $credential->update(['is_valid' => false]);
    }

    public function removeCredential(User $user, AiProvider $provider): bool
    {
        return (bool) $user->aiCredentials()
            ->where('provider', $provider->value)
            ->delete();
    }
}
