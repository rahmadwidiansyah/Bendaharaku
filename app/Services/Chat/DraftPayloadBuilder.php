<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\DTO\ParsedTransaction;
use App\DTO\ResolvedTransaction;
use App\Support\MoneyFormatter;

/**
 * SSOT untuk validasi parsed transaction dan pembangunan payload draft.
 *
 * Diekstrak dari Closure-injection yang sebelumnya ada di MultiTransactionProcessor,
 * sehingga kedua kelas (ChatTransactionOrchestrator & MultiTransactionProcessor)
 * dapat menggunakannya tanpa duplikasi implementasi.
 */
class DraftPayloadBuilder
{
    /**
     * Validasi dasar parsed transaction sebelum diproses menjadi draft.
     *
     * @return string|null Kode error ('INVALID_AMOUNT' | 'CATEGORY_NOT_FOUND') atau null jika valid.
     */
    public function validateParsed(ParsedTransaction $parsed): ?string
    {
        if (! $parsed->amount || $parsed->amount <= 0) {
            return 'INVALID_AMOUNT';
        }

        if (! $parsed->category) {
            return 'CATEGORY_NOT_FOUND';
        }

        return null;
    }

    /**
     * Bangun array payload yang disimpan ke kolom `payload` pada tabel transaction_drafts.
     */
    public function build(
        ResolvedTransaction $resolved,
        string $categoryName,
        ?string $sourceWalletName,
        ?string $destinationWalletName,
        ?string $subject,
        string $notes,
        string $typeKey,
        bool $needsWallet,
        array $aiKeywords = [],
    ): array {
        return [
            'amount' => $resolved->amount,
            'category_id' => $resolved->categoryId,
            'category_name' => $categoryName,
            'source_wallet_id' => $resolved->sourceWalletId,
            'source_wallet_name' => $sourceWalletName,
            'destination_wallet_id' => $resolved->destinationWalletId,
            'destination_wallet_name' => $destinationWalletName,
            'subject' => $subject,
            'notes' => $notes,
            'type_key' => $typeKey,
            'needs_wallet' => $needsWallet,
            'is_cleared' => false,
            'date' => now()->format('Y-m-d'),
            'amount_formatted' => MoneyFormatter::rupiah($resolved->amount),
            'aiKeywords' => $aiKeywords,
        ];
    }
}
