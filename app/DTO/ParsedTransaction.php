<?php

namespace App\DTO;

readonly class ParsedTransaction
{
    public function __construct(
        public int $userId,
        public string $transactionType, // 'Income', 'Expense', 'Transfer', 'Debt', 'Receivable'
        public string $categoryKeyword,  // Nama kategori atau kata kunci unik
        public string $sourceWalletKeyword, // Nama dompet sumber atau kata kunci unik
        public string $destinationWalletKeyword, // Nama dompet tujuan atau kata kunci unik
        public float|int $amount,
        public string $subject,
        public ?string $notes = null,
        public bool $isCleared = true,
        public string $sourceType = 'WEB', // 'WEB' atau 'TELEGRAM'
        public ?string $date = null,
        public ?string $dueDate = null,
        public ?string $dueDateType = null,
        public ?int $dueDateInterval = null
    ) {}
}