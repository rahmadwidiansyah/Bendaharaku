<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\User;

/**
 * DTO untuk membawa semua konteks yang dibutuhkan ConfidenceScoringEngine.
 * Dibuat sebagai pengganti 4 parameter terpisah (BUG-01 fix).
 */
readonly class ConfidenceScoreContext
{
    public function __construct(
        public User $user,
        public string $inputText,
        public AIParseResult $parseResult,
        public ?ResolvedTransaction $resolvedTransaction,
        public array $activeMemories = [],
        public array $wallets = [],
        public array $categories = [],
    ) {}
}
