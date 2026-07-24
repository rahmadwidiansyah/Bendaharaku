<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * DTO untuk membawa semua konteks yang dibutuhkan ConfidenceScoringEngine.
 * Dibuat sebagai pengganti 4 parameter terpisah (BUG-01 fix).
 */
readonly class ConfidenceScoreContext
{
    /**
     * @param  Collection<int, \App\Models\Wallet>|null  $wallets
     * @param  Collection<int, \App\Models\Category>|null  $categories
     */
    public function __construct(
        public User $user,
        public string $inputText,
        public AIParseResult $parseResult,
        public ?ResolvedTransaction $resolvedTransaction,
        public array $activeMemories = [],
        public ?Collection $wallets = null,
        public ?Collection $categories = null,
    ) {}
}
