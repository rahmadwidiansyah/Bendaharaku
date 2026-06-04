<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\TransactionIntent;

/**
 * DTO Immutabel hasil ekstraksi AI.
 * Menggunakan strong-typing Enum untuk mendefinisikan intent transaksi.
 */
readonly class ParsedTransaction
{
    public function __construct(
        public float|int $amount,
        public ?TransactionIntent $transactionType = null, // Strongly-typed Enum
        public ?string $category = null,
        public ?string $sourceWallet = null,
        public ?string $destinationWallet = null,
        public ?string $subject = null,
        public ?string $notes = null,
        public bool $isCleared = true
    ) {}
}