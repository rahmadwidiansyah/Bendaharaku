<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\MemoryCandidate;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use PHPUnit\Framework\TestCase;

class ParsedTransactionContractTest extends TestCase
{
    public function test_it_exposes_separate_keywords_and_memory_candidates(): void
    {
        $transaction = new ParsedTransaction(
            amount: 20000,
            transactionType: TransactionIntent::Expense,
            category: 'Makan',
            categoryKeyword: 'bakso',
            sourceWallet: 'Dompet Tunai',
            sourceWalletKeyword: 'cash',
            memoryCandidates: [new MemoryCandidate('bakso', 'category', 'Makan')],
        );

        self::assertSame('bakso', $transaction->categoryKeyword);
        self::assertSame('cash', $transaction->sourceWalletKeyword);
        self::assertCount(1, $transaction->memoryCandidates);
        self::assertSame('category', $transaction->memoryCandidates[0]->targetType);
    }

    public function test_new_fields_are_optional_for_existing_callers(): void
    {
        $transaction = new ParsedTransaction(amount: 1000);

        self::assertNull($transaction->categoryKeyword);
        self::assertNull($transaction->sourceWalletKeyword);
        self::assertNull($transaction->destinationWalletKeyword);
        self::assertSame([], $transaction->memoryCandidates);
    }
}
