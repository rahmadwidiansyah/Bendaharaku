<?php

declare(strict_types=1);

namespace App\Evidence\Resolver;

use App\Evidence\DTO\EvidenceData;
use App\Evidence\DTO\TransactionDraft;
use App\Models\Evidence;
use Illuminate\Support\Facades\Log;

/**
 * EvidenceResolver — Orchestrator untuk resolve EvidenceData menjadi TransactionDraft.
 *
 * Menggunakan sub-resolvers:
 * - WalletResolver
 * - MerchantResolver
 * - CategoryResolver
 * - DuplicateResolver
 * - TransferResolver
 */
class EvidenceResolver
{
    public function __construct(
        private WalletResolver $walletResolver,
        private MerchantResolver $merchantResolver,
        private CategoryResolver $categoryResolver,
        private DuplicateResolver $duplicateResolver,
        private TransferResolver $transferResolver,
    ) {}

    /**
     * Resolve EvidenceData menjadi TransactionDraft.
     */
    public function resolve(Evidence $evidence, EvidenceData $data): TransactionDraft
    {
        $user = $evidence->user;

        Log::info('Evidence resolver started', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'document_type' => $data->documentType->value,
        ]);

        $warnings = [];
        $confidences = [];
        $metadata = [];

        // ── 1. Transfer Resolver (detect internal transfer) ───────────
        $transferResult = $this->transferResolver->resolve(
            $user,
            $data->destinationAccount,
            $data->destinationName,
            $data->transactionType,
        );

        $transactionType = $transferResult['transaction_type'];
        $destinationWalletId = $transferResult['destination_wallet_id'];
        $destinationWalletName = $transferResult['destination_wallet_name'];
        $confidences[] = $transferResult['confidence'];
        $metadata['transfer'] = $transferResult;

        if ($transferResult['is_internal']) {
            $warnings[] = 'Transfer internal terdeteksi';
        }

        // ── 2. Wallet Resolver (source) ──────────────────────────────
        $sourceWallet = $this->walletResolver->resolveSource($user, $data->walletName, $data->bankName);
        $confidences[] = $sourceWallet['confidence'];
        $metadata['wallet_source'] = $sourceWallet;

        // ── 3. Merchant Resolver ─────────────────────────────────────
        $merchant = $this->merchantResolver->resolve($user, $data->merchantName);
        $confidences[] = $merchant['confidence'];
        $metadata['merchant'] = $merchant;

        // ── 4. Category Resolver ─────────────────────────────────────
        $category = $this->categoryResolver->resolve(
            $user,
            $data->transactionType,
            $data->documentType->value,
            $data->description,
            $merchant['merchant_category'] ?? null,
            $data->merchantName,
        );
        $confidences[] = $category['confidence'];
        $metadata['category'] = $category;

        // ── 5. Duplicate Resolver ────────────────────────────────────
        $duplicate = $this->duplicateResolver->resolve(
            $user,
            $data->referenceNumber,
            $data->amount,
            $sourceWallet['wallet_id'],
            $data->transactionTime,
        );

        if ($duplicate['is_duplicate']) {
            $warnings = array_merge($warnings, $duplicate['warnings']);
        }

        $confidences[] = $duplicate['confidence'];
        $metadata['duplicate'] = $duplicate;

        // ── Hitung overall confidence ────────────────────────────────
        $validConfidences = array_filter($confidences, fn ($c) => $c > 0);
        $overallConfidence = count($validConfidences) > 0
            ? array_sum($validConfidences) / count($validConfidences)
            : 0.0;

        // ── Build TransactionDraft ───────────────────────────────────
        $draft = new TransactionDraft(
            transactionType: $transactionType,
            walletId: $sourceWallet['wallet_id'],
            walletName: $sourceWallet['wallet_name'],
            categoryId: $category['category_id'],
            categoryName: $category['category_name'],
            merchantName: $merchant['merchant_name'],
            amount: $data->amount ?? 0.0,
            currency: $data->currency,
            description: $data->description,
            transactionDate: $data->transactionTime,
            referenceNumber: $data->referenceNumber,
            destinationName: $data->destinationName,
            destinationAccount: $data->destinationAccount,
            destinationWalletId: $destinationWalletId,
            confidence: round($overallConfidence, 4),
            warnings: $warnings,
            metadata: $metadata,
            resolved: $overallConfidence >= 0.5 && ! $duplicate['is_duplicate'],
            // Per-field confidence
            amountConfidence: $data->amount > 0 ? 0.95 : 0.0,
            walletConfidence: $sourceWallet['confidence'],
            categoryConfidence: $category['confidence'],
            destinationNameConfidence: $transferResult['destination_wallet_id'] ? $transferResult['confidence'] : ($data->destinationName ? 0.6 : 0.0),
            destinationAccountConfidence: $data->destinationAccount ? 0.8 : 0.0,
            dateConfidence: $data->transactionTime ? 0.85 : 0.0,
            referenceConfidence: $data->referenceNumber ? 0.9 : 0.0,
        );

        Log::info('Evidence resolver completed', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'transaction_type' => $transactionType,
            'wallet_id' => $sourceWallet['wallet_id'],
            'category_id' => $category['category_id'],
            'confidence' => $draft->confidence,
            'resolved' => $draft->resolved,
            'warnings' => $warnings,
        ]);

        return $draft;
    }
}
