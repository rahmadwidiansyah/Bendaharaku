<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use App\DTO\AIParseResult;
use App\DTO\ConfidenceScoreContext;
use App\DTO\ParsedTransaction;
use App\DTO\ResolvedTransaction;
use App\Enums\TransactionIntent;
use App\Services\AI\Scoring\ConfidenceScoringEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfidenceScoringCollectionContractTest extends TestCase
{
    use RefreshDatabase;
    use RegressionTestHelpers;

    private ConfidenceScoringEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRegressionData();
        $this->engine = $this->app->make(ConfidenceScoringEngine::class);
    }

    public function test_single_transaction_scoring_accepts_eloquent_collections(): void
    {
        $score = $this->score(new ParsedTransaction(
            amount: 15000,
            transactionType: TransactionIntent::Expense,
            category: 'makan',
            sourceWallet: 'cash',
        ));

        $this->assertGreaterThan(0.0, $score);
    }

    public function test_multi_transaction_items_score_with_the_same_eloquent_collections(): void
    {
        $items = [
            new ParsedTransaction(
                amount: 15000,
                transactionType: TransactionIntent::Expense,
                category: 'makan',
                sourceWallet: 'cash',
            ),
            new ParsedTransaction(
                amount: 50000,
                transactionType: TransactionIntent::Expense,
                category: 'bensin',
                sourceWallet: 'bca',
            ),
        ];

        foreach ($items as $item) {
            $this->assertGreaterThan(0.0, $this->score($item));
        }
    }

    public function test_ocr_transaction_scoring_accepts_eloquent_collections(): void
    {
        $score = $this->score(
            new ParsedTransaction(
                amount: 25000,
                transactionType: TransactionIntent::Expense,
                category: 'bakso',
                sourceWallet: 'cash',
                notes: 'OCR receipt: Warung Bakso Rp25.000',
            ),
            provider: 'ocr'
        );

        $this->assertGreaterThan(0.0, $score);
    }

    public function test_transfer_scoring_accepts_eloquent_wallet_collections(): void
    {
        $score = $this->score(new ParsedTransaction(
            amount: 100000,
            transactionType: TransactionIntent::Transfer,
            sourceWallet: 'bca',
            destinationWallet: 'cash',
        ));

        $this->assertGreaterThan(0.0, $score);
    }

    private function score(ParsedTransaction $parsed, string $provider = 'test'): float
    {
        $parseResult = new AIParseResult(
            success: true,
            confidence: 0.95,
            error: null,
            transaction: $parsed,
            provider: $provider,
        );

        return $this->engine->calculateFinalScore(new ConfidenceScoreContext(
            user: $this->user,
            inputText: $parsed->notes ?? 'test transaction',
            parseResult: $parseResult,
            resolvedTransaction: new ResolvedTransaction(
                amount: $parsed->amount,
                categoryId: $this->foodCategory->id,
                sourceWalletId: $this->cashWallet->id,
                destinationWalletId: $this->merchantWallet->id,
                subject: $this->user->name,
                notes: $parsed->notes,
                isCleared: true,
            ),
            wallets: $this->user->wallets()->get(['name', 'keyword', 'balance', 'group_type']),
            categories: $this->user->categories()->get(['category_name', 'keyword']),
        ));
    }
}
