<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Evidence\DTO\TransactionDraft;
use App\Models\Evidence;
use App\Services\Evidence\EvidenceCommitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * EvidenceReviewController — HTTP layer untuk review & edit draft transaksi dari evidence.
 *
 * Tanggung jawab:
 * - GET: Return draft data dari evidence yang sudah resolved
 * - PATCH: Update draft data (user edits) dan simpan kembali
 * - POST: Commit draft menjadi transaksi nyata
 */
class EvidenceReviewController extends Controller
{
    public function __construct(
        private readonly EvidenceCommitService $commitService,
    ) {}

    /**
     * GET /api/chat/evidence/{uuid}/draft
     *
     * Return draft data untuk review user.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        $evidence = Evidence::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->first();

        if (! $evidence) {
            return response()->json([
                'success' => false,
                'message' => 'Evidence tidak ditemukan.',
            ], 404);
        }

        if (! $evidence->isResolved() && $evidence->status->value !== 'READY') {
            return response()->json([
                'success' => false,
                'message' => 'Evidence belum selesai diproses.',
            ], 422);
        }

        $draft = $evidence->resolved_data;

        if (! $draft) {
            return response()->json([
                'success' => false,
                'message' => 'Draft data tidak tersedia.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'evidence' => [
                'uuid' => $evidence->uuid,
                'status' => $evidence->status->value,
                'document_type' => $evidence->document_type?->value,
                'original_name' => $evidence->original_name,
                'url' => $evidence->url,
            ],
            'draft' => $draft->toArray(),
        ]);
    }

    /**
     * PATCH /api/chat/evidence/{uuid}/draft
     *
     * Update draft data berdasarkan edit user.
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        $evidence = Evidence::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->first();

        if (! $evidence) {
            return response()->json([
                'success' => false,
                'message' => 'Evidence tidak ditemukan.',
            ], 404);
        }

        if (! $evidence->isResolved() && $evidence->status->value !== 'READY') {
            return response()->json([
                'success' => false,
                'message' => 'Evidence belum selesai diproses.',
            ], 422);
        }

        $validated = $request->validate([
            'transaction_type' => 'nullable|string|in:EXPENSE,INCOME,TRANSFER,INTERNAL_TRANSFER',
            'wallet_id' => 'nullable|integer|exists:wallets,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'transaction_date' => 'nullable|string',
            'destination_name' => 'nullable|string|max:200',
            'destination_account' => 'nullable|string|max:50',
        ]);

        $currentDraft = $evidence->resolved_data;

        if (! $currentDraft) {
            return response()->json([
                'success' => false,
                'message' => 'Draft data tidak tersedia.',
            ], 404);
        }

        // Build new draft with user edits
        $newDraft = new TransactionDraft(
            transactionType: $validated['transaction_type'] ?? $currentDraft->transactionType,
            walletId: $validated['wallet_id'] ?? $currentDraft->walletId,
            walletName: $validated['wallet_id'] ? null : $currentDraft->walletName,
            categoryId: $validated['category_id'] ?? $currentDraft->categoryId,
            categoryName: $validated['category_id'] ? null : $currentDraft->categoryName,
            merchantName: $currentDraft->merchantName,
            amount: $validated['amount'] ?? $currentDraft->amount,
            currency: $currentDraft->currency,
            description: $validated['description'] ?? $currentDraft->description,
            transactionDate: $validated['transaction_date'] ?? $currentDraft->transactionDate,
            referenceNumber: $currentDraft->referenceNumber,
            destinationName: $validated['destination_name'] ?? $currentDraft->destinationName,
            destinationAccount: $validated['destination_account'] ?? $currentDraft->destinationAccount,
            destinationWalletId: $currentDraft->destinationWalletId,
            confidence: $currentDraft->confidence,
            warnings: $currentDraft->warnings,
            metadata: $currentDraft->metadata,
            resolved: true,
            // Per-field confidence — user edits get confidence = 1.0
            amountConfidence: isset($validated['amount']) ? 1.0 : $currentDraft->amountConfidence,
            walletConfidence: isset($validated['wallet_id']) ? 1.0 : $currentDraft->walletConfidence,
            categoryConfidence: isset($validated['category_id']) ? 1.0 : $currentDraft->categoryConfidence,
            destinationNameConfidence: isset($validated['destination_name']) ? 1.0 : $currentDraft->destinationNameConfidence,
            destinationAccountConfidence: isset($validated['destination_account']) ? 1.0 : $currentDraft->destinationAccountConfidence,
            dateConfidence: isset($validated['transaction_date']) ? 1.0 : $currentDraft->dateConfidence,
            referenceConfidence: $currentDraft->referenceConfidence,
        );

        // Save updated draft
        $evidence->update([
            'resolved_data' => $newDraft->toArray(),
            'status' => 'READY',
        ]);

        Log::info('Evidence draft updated', [
            'evidence_id' => $evidence->id,
            'uuid' => $evidence->uuid,
            'user_id' => $user->id,
            'fields_updated' => array_keys($validated),
        ]);

        return response()->json([
            'success' => true,
            'draft' => $newDraft->toArray(),
        ]);
    }

    /**
     * POST /api/chat/evidence/{uuid}/commit
     *
     * Commit draft menjadi transaksi nyata.
     */
    public function commit(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        $evidence = Evidence::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->first();

        if (! $evidence) {
            return response()->json([
                'success' => false,
                'message' => 'Evidence tidak ditemukan.',
            ], 404);
        }

        // Allow commit for READY or already committed (idempotent)
        if (! $evidence->isReady() && ! $evidence->isCompleted() && ! $evidence->isResolved()) {
            return response()->json([
                'success' => false,
                'message' => 'Evidence belum siap untuk di-commit.',
            ], 422);
        }

        $overrides = $request->validate([
            'transaction_type' => 'nullable|string|in:EXPENSE,INCOME,TRANSFER,INTERNAL_TRANSFER',
            'wallet_id' => 'nullable|integer|exists:wallets,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'transaction_date' => 'nullable|string',
            'destination_name' => 'nullable|string|max:200',
            'destination_account' => 'nullable|string|max:50',
        ]);

        $result = $this->commitService->commit($evidence, $overrides);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'transaction_id' => $result['transaction_id'],
                'status' => $result['status'],
                'message' => $result['message'],
                'transaction' => $result['transaction'] ?? null,
                'warnings' => $result['warnings'] ?? [],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 422);
    }
}
