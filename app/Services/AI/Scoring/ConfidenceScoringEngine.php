<?php

declare(strict_types=1);

namespace App\Services\AI\Scoring;

use App\DTO\AIParseResult;
use App\DTO\ConfidenceScoreContext;
use App\Enums\TransactionIntent;
use App\Models\User;
use App\Services\AI\Scoring\Matchers\CategoryMatchService;
use App\Services\AI\Scoring\Matchers\MemoryMatchService;
use App\Services\AI\Scoring\Matchers\WalletMatchService;

readonly class ConfidenceScoringEngine
{
    public function __construct(
        private CategoryMatchService $categoryMatch,
        private WalletMatchService $walletMatch,
        private MemoryMatchService $memoryMatch
    ) {}

    /**
     * Kalkulasi skor kepercayaan final dari ConfidenceScoreContext.
     * BUG-01 fix: menerima DTO tunggal agar konsisten dengan Orchestrator.
     */
    public function calculateFinalScore(ConfidenceScoreContext $context): float
    {
        $user = $context->user;
        $inputText = $context->inputText;
        $parseResult = $context->parseResult;
        $activeMemories = $context->activeMemories;

        $weights = config('bendaharaku.ai.confidence.weights');
        $finalScore = 0.0000;

        // 1. AI Base Score
        $aiRaw = max(0.0, min(1.0, (float) $parseResult->confidence));
        $finalScore += $aiRaw * $weights['ai_base'];

        // 2. Memory Match Score
        $memoryScore = $this->memoryMatch->calculateScore($inputText, $activeMemories);
        $finalScore += $memoryScore * $weights['memory_match'];

        // 3. Category Match Score
        if ($this->categoryMatch->isMatch($user, $parseResult->transaction?->category)) {
            $finalScore += 1.0 * $weights['category_match'];
        }

        // 4. Wallet Match Score (Contextual Intent Logic)
        $walletScore = $this->calculateWalletScore($user, $parseResult);
        $finalScore += $walletScore * $weights['wallet_match'];

        return min(1.0000, round($finalScore, 4));
    }

    private function calculateWalletScore(User $user, AIParseResult $result): float
    {
        $t = $result->transaction;
        if (! $t) {
            return 0.0;
        }

        $sourceMatch = $this->walletMatch->isMatch($user, $t->sourceWallet);
        $destMatch = $this->walletMatch->isMatch($user, $t->destinationWallet);

        return match ($t->transactionType) {
            TransactionIntent::Expense => $sourceMatch ? 1.0 : 0.0,
            TransactionIntent::Income => ($sourceMatch || $destMatch) ? 1.0 : 0.0,
            TransactionIntent::Transfer,
            TransactionIntent::Debt,
            TransactionIntent::Receivable => ($sourceMatch && $destMatch) ? 1.0 : ($sourceMatch || $destMatch ? 0.5 : 0.0),
            default => 0.0,
        };
    }
}
