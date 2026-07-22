<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AIParseResult;
use App\DTO\ParsedTransaction;

class TransactionValidationService
{
    public function validateAndGuard(AIParseResult $result): AIParseResult
    {
        if (! $result->success || ! $result->transaction) {
            return $result;
        }

        $trx = $result->transaction;

        // 1. Hard Validation (Tolak langsung jika tidak logis)
        if ($trx->amount <= 0) {
            return AIParseResult::failure('Validasi Gagal: Nominal transaksi harus lebih besar dari nol.');
        }
        if (empty($trx->transactionType)) {
            return AIParseResult::failure('Validasi Gagal: Tipe transaksi tidak dapat diidentifikasi.');
        }

        // 2. Confidence Guard Strategy (Drafting)
        // Jika confidence di bawah 80%, paksa transaksi menjadi DRAFT (isCleared = false)
        if ($result->confidence < 0.80) {
            $guardedTransaction = new ParsedTransaction(
                amount: $trx->amount,
                transactionType: $trx->transactionType,
                category: $trx->category,
                sourceWallet: $trx->sourceWallet,
                destinationWallet: $trx->destinationWallet,
                subject: $trx->subject,
                notes: $trx->notes." [WARNING: LOW CONFIDENCE {$result->confidence}]",
                isCleared: false // FORCED DRAFT
            );

            return new AIParseResult(
                success: true,
                confidence: $result->confidence,
                error: null,
                transaction: $guardedTransaction
            );
        }

        return $result;
    }
}
