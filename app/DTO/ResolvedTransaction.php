<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Support\Facades\Log;

/**
 * DTO Immutabel yang murni membawa data primitif hasil resolusi entitas database.
 * Bersih dari ketergantungan model Eloquent maupun Enum bisnis eksternal.
 * Diperketat untuk memenuhi standar ProcessTransactionAction (destinationWalletId wajib ada).
 */
readonly class ResolvedTransaction
{
public function __construct(
    public float|int $amount,
    public ?int $categoryId,
    public ?int $sourceWalletId,
    public ?int $destinationWalletId,
    public ?string $subject,
    public ?string $notes,
    public bool $isCleared,
    public ?string $missingWalletSide = null,
) {
    // Temporary trace log (removed after RCA)
    Log::debug('[PIPELINE:CRC] ResolvedTransaction::__construct', [
        'trace_id' => null,
        'class' => self::class,
        'method' => '__construct',
        'sourceWalletId' => $this->sourceWalletId,
        'destinationWalletId' => $this->destinationWalletId,
        'transactionType' => null,
        'missingWalletSide' => $this->missingWalletSide,
    ]);
}
}
