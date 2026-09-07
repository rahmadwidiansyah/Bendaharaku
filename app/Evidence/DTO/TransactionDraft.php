<?php

declare(strict_types=1);

namespace App\Evidence\DTO;

/**
 * TransactionDraft — DTO hasil resolver yang siap di-review user.
 *
 * DTO ini menjadi format output untuk seluruh resolver.
 * Belum menyimpan transaksi — hanya draft.
 */
readonly class TransactionDraft
{
    public function __construct(
        public string $transactionType,
        public ?int $walletId = null,
        public ?string $walletName = null,
        public ?int $categoryId = null,
        public ?string $categoryName = null,
        public ?string $merchantName = null,
        public ?float $amount = null,
        public ?string $currency = null,
        public ?string $description = null,
        public ?string $transactionDate = null,
        public ?string $referenceNumber = null,
        public ?string $destinationName = null,
        public ?string $destinationAccount = null,
        public ?int $destinationWalletId = null,
        public float $confidence = 0.0,
        public array $warnings = [],
        public array $metadata = [],
        public bool $resolved = false,
        // Per-field confidence scores (0.0 - 1.0)
        public float $amountConfidence = 0.0,
        public float $walletConfidence = 0.0,
        public float $categoryConfidence = 0.0,
        public float $destinationNameConfidence = 0.0,
        public float $destinationAccountConfidence = 0.0,
        public float $dateConfidence = 0.0,
        public float $referenceConfidence = 0.0,
    ) {}

    /**
     * Konversi ke array untuk disimpan sebagai JSON di database.
     */
    public function toArray(): array
    {
        return [
            'transaction_type' => $this->transactionType,
            'wallet_id' => $this->walletId,
            'wallet_name' => $this->walletName,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'merchant_name' => $this->merchantName,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'transaction_date' => $this->transactionDate,
            'reference_number' => $this->referenceNumber,
            'destination_name' => $this->destinationName,
            'destination_account' => $this->destinationAccount,
            'destination_wallet_id' => $this->destinationWalletId,
            'confidence' => $this->confidence,
            'warnings' => $this->warnings,
            'metadata' => $this->metadata,
            'resolved' => $this->resolved,
            // Per-field confidence
            'amount_confidence' => $this->amountConfidence,
            'wallet_confidence' => $this->walletConfidence,
            'category_confidence' => $this->categoryConfidence,
            'destination_name_confidence' => $this->destinationNameConfidence,
            'destination_account_confidence' => $this->destinationAccountConfidence,
            'date_confidence' => $this->dateConfidence,
            'reference_confidence' => $this->referenceConfidence,
        ];
    }

    /**
     * Buat instance dari array (untuk decoding dari database).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionType: $data['transaction_type'] ?? 'EXPENSE',
            walletId: $data['wallet_id'] ?? null,
            walletName: $data['wallet_name'] ?? null,
            categoryId: $data['category_id'] ?? null,
            categoryName: $data['category_name'] ?? null,
            merchantName: $data['merchant_name'] ?? null,
            amount: $data['amount'] ?? null,
            currency: $data['currency'] ?? null,
            description: $data['description'] ?? null,
            transactionDate: $data['transaction_date'] ?? null,
            referenceNumber: $data['reference_number'] ?? null,
            destinationName: $data['destination_name'] ?? null,
            destinationAccount: $data['destination_account'] ?? null,
            destinationWalletId: $data['destination_wallet_id'] ?? null,
            confidence: $data['confidence'] ?? 0.0,
            warnings: $data['warnings'] ?? [],
            metadata: $data['metadata'] ?? [],
            resolved: $data['resolved'] ?? false,
            // Per-field confidence
            amountConfidence: $data['amount_confidence'] ?? 0.0,
            walletConfidence: $data['wallet_confidence'] ?? 0.0,
            categoryConfidence: $data['category_confidence'] ?? 0.0,
            destinationNameConfidence: $data['destination_name_confidence'] ?? 0.0,
            destinationAccountConfidence: $data['destination_account_confidence'] ?? 0.0,
            dateConfidence: $data['date_confidence'] ?? 0.0,
            referenceConfidence: $data['reference_confidence'] ?? 0.0,
        );
    }
}
