<?php

declare(strict_types=1);

namespace App\Services\AI\Context;

use App\Models\User;
use App\Services\AI\Memory\UserMemoryService;
use Illuminate\Database\Eloquent\Collection;

readonly class ContextSnapshot
{
    public function __construct(
        public User $user,
        public string $userInput,
        public Collection $wallets,
        public Collection $categories,
        public array $activeMemories,
    ) {}

    public static function load(User $user, string $text): self
    {
        return new self(
            user: $user,
            userInput: $text,
            wallets: $user->wallets()->get(),
            categories: $user->categories()->get(),
            activeMemories: app(UserMemoryService::class)->getTopRelevantMemories(
                $user->id, $text
            ),
        );
    }
}
