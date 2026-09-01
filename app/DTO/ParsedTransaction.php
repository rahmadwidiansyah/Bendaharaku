<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\TransactionIntent;

/**
 * DTO Immutabel hasil ekstraksi AI.
 * Menggunakan strong-typing Enum untuk mendefinisikan intent transaksi.
 *
 * Field useAllBalance: jika true, backend akan mengganti amount dengan saldo aktual
 * wallet sumber saat transaksi dieksekusi. AI mengembalikan amount=0 dan useAllBalance=true
 * untuk perintah seperti "pindahkan semua saldo BCA ke Cash".
 */
readonly class ParsedTransaction
{
    public function __construct(
        public float|int $amount,
        public ?TransactionIntent $transactionType = null, // Strongly-typed Enum
        public ?string $category = null,
        public ?string $categoryKeyword = null,
        public ?string $sourceWallet = null,
        public ?string $sourceWalletKeyword = null,
        public ?string $destinationWallet = null,
        public ?string $destinationWalletKeyword = null,
        /** @var MemoryCandidate[] */
        public array $memoryCandidates = [],
        public ?string $subject = null,
        public ?string $notes = null,
        public bool $isCleared = true,
        public bool $useAllBalance = false // Jika true, backend isi amount dari saldo aktual wallet asal
    ) {}
}
