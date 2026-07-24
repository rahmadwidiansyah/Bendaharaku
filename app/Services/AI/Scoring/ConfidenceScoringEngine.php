<?php

declare(strict_types=1);

namespace App\Services\AI\Scoring;

use App\DTO\ConfidenceScoreContext;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Services\AI\Scoring\Matchers\CategoryMatchService;
use App\Services\AI\Scoring\Matchers\MemoryMatchService;
use App\Services\AI\Scoring\Matchers\WalletMatchService;
use App\Support\StringUtils;
use Illuminate\Database\Eloquent\Collection;

readonly class ConfidenceScoringEngine
{
    public function __construct(
        private CategoryMatchService $categoryMatch,
        private WalletMatchService $walletMatch,
        private MemoryMatchService $memoryMatch,
    ) {}

    public function calculateFinalScore(ConfidenceScoreContext $context): float
    {
        $user = $context->user;
        $inputText = $context->inputText;
        $parseResult = $context->parseResult;
        $activeMemories = $context->activeMemories;

        $weights = config('bendaharaku.ai.confidence.weights');
        $finalScore = 0.0000;

        $aiRaw = max(0.0, min(1.0, (float) $parseResult->confidence));
        $finalScore += $aiRaw * $weights['ai_base'];

        $memoryScore = $this->memoryMatch->calculateScore($inputText, $activeMemories);
        $finalScore += $memoryScore * $weights['memory_match'];

        $categories = $context->categories !== null
            ? $context->categories
            : $user->categories()->get(['category_name', 'keyword']);

        if ($this->categoryMatch->isMatch($categories, $parseResult->transaction?->category)) {
            $finalScore += 1.0 * $weights['category_match'];
        }

        $walletScore = $this->calculateWalletScore($context, $parseResult);
        $finalScore += $walletScore * $weights['wallet_match'];

        return min(1.0000, round($finalScore, 4));
    }

    private function calculateWalletScore(ConfidenceScoreContext $context, $result): float
    {
        $t = $result->transaction;
        if (! $t) {
            return 0.0;
        }

        $wallets = $context->wallets !== null
            ? $context->wallets
            : $context->user->wallets()->get(['name', 'keyword', 'balance', 'group_type']);

        $sourceMatch = $this->walletMatch->isMatch($wallets, $t->sourceWallet);
        $destMatch = $this->walletMatch->isMatch($wallets, $t->destinationWallet);

        $baseScore = match ($t->transactionType) {
            TransactionIntent::Expense => $sourceMatch ? 1.0 : 0.0,
            TransactionIntent::Income => ($sourceMatch || $destMatch) ? 1.0 : 0.0,
            TransactionIntent::Transfer,
            TransactionIntent::Debt,
            TransactionIntent::Receivable => ($sourceMatch && $destMatch) ? 1.0 : ($sourceMatch || $destMatch ? 0.5 : 0.0),
            default => 0.0,
        };

        if ($baseScore <= 0.0 || $t->amount <= 0) {
            return $baseScore;
        }

        $balanceFactor = $this->balanceFactor($t, $wallets);

        return round($baseScore * $balanceFactor, 4);
    }

    private function balanceFactor(ParsedTransaction $t, Collection $wallets): float
    {
        $amount = $t->amount;

        $walletName = match ($t->transactionType) {
            TransactionIntent::Expense => $t->sourceWallet,
            TransactionIntent::Transfer => $t->sourceWallet,
            TransactionIntent::Income => null,
            TransactionIntent::Debt => $t->sourceWallet,
            TransactionIntent::Receivable => $t->sourceWallet,
            default => null,
        };

        if (blank($walletName)) {
            return 1.0;
        }

        $matched = StringUtils::findByNameOrKeyword($wallets, $walletName);
        if (! $matched || ($matched->group_type ?? '') === 'System') {
            return 1.0;
        }

        $balance = (float) ($matched->balance ?? 0);
        if ($balance >= $amount) {
            return 1.0;
        }

        $ratio = $balance / max($amount, 1);

        return round(0.5 + ($ratio * 0.5), 4);
    }
}
