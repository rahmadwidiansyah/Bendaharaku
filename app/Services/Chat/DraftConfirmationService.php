<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\TransactionDraft;
use App\Models\User;
use App\Models\Wallet;
use App\Models\ChatMessage;
use App\Actions\ProcessTransactionAction;
use App\Support\MoneyFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * DraftConfirmationService — Mengonversi TransactionDraft menjadi TransactionLog.
 *
 * Tanggung jawab:
 * 1. confirm()      — Draft sudah punya wallet lengkap → buat transaction_log
 * 2. assignWallet() — User pilih wallet untuk draft yang butuh wallet, lalu konfirmasi langsung
 * 3. cancel()       — Tandai draft sebagai cancelled
 *
 * Tidak ada business rule saldo di sini — semua mutasi saldo diserahkan ke ProcessTransactionAction.
 */
class DraftConfirmationService
{
    public function __construct(
        private readonly ProcessTransactionAction $transactionAction,
    ) {}

    /**
     * Konfirmasi draft: buat TransactionLog dari payload draft.
     * Hanya berlaku untuk draft dengan status 'pending'.
     *
     * @return \App\Models\TransactionLog
     * @throws InvalidArgumentException jika draft sudah dikonfirmasi/dibatalkan/expired
     */
    public function confirm(TransactionDraft $draft, User $user): \App\Models\TransactionLog
    {
        if ($draft->isConfirmed() && !empty($draft->confirmed_transaction_ids)) {
            $logId = $draft->confirmed_transaction_ids[0];
            $existingLog = \App\Models\TransactionLog::find($logId);
            if ($existingLog) {
                return $existingLog->load(['category', 'sourceWallet', 'destinationWallet', 'type']);
            }
        }

        if (!$draft->isPending()) {
            throw new InvalidArgumentException(
                "Draft #{$draft->id} tidak dapat dikonfirmasi (status: {$draft->status})."
            );
        }

        $payload = $draft->payload;

        // Validasi payload minimal
        if (empty($payload['source_wallet_id']) || empty($payload['destination_wallet_id'])) {
            throw new InvalidArgumentException(
                "Draft #{$draft->id} belum memiliki wallet lengkap. Gunakan assignWallet() terlebih dahulu."
            );
        }

        $transactionLog = DB::transaction(function () use ($draft, $user, $payload) {
            // Buat TransactionLog via ProcessTransactionAction
            // ProcessTransactionAction::create() menangani mutasi saldo, reference number, dll.
            $log = $this->transactionAction->create([
                'date'                  => $payload['date'] ?? now()->format('Y-m-d'),
                'category_id'           => $payload['category_id'],
                'source_wallet_id'      => $payload['source_wallet_id'],
                'destination_wallet_id' => $payload['destination_wallet_id'],
                'amount'                => $payload['amount'],
                'subject'               => $payload['subject'] ?? $user->name,
                'notes'                 => $payload['notes'] ?? null,
                'is_cleared'            => true, // Konfirmasi → langsung cleared
            ], $user->id, 'WEB');

            // Tandai draft sebagai confirmed + simpan referensi ke transaction_log
            $draft->update([
                'status'                    => 'confirmed',
                'confirmed_transaction_ids' => [$log->id],
            ]);

            return $log;
        });

        // Sinkronkan riwayat chat setelah konfirmasi
        $this->syncChatHistoryAfterConfirm($user->id, $draft->id, $transactionLog);

        Log::info('DraftConfirmationService: draft dikonfirmasi', [
            'draft_id'       => $draft->id,
            'transaction_id' => $transactionLog->id,
            'user_id'        => $user->id,
        ]);

        return $transactionLog->load(['category', 'sourceWallet', 'destinationWallet', 'type']);
    }

    /**
     * Assign wallet ke draft yang butuh wallet (needs_wallet = true), lalu konfirmasi langsung.
     *
     * Logika resolusi wallet (sama dengan assignWallet() lama di WebChatController):
     * - Jika source wallet adalah System wallet "bermakna" (Hutang/Piutang) → user mengisi dest
     * - Sebaliknya → user mengisi source
     *
     * @return \App\Models\TransactionLog
     * @throws InvalidArgumentException
     */
    public function assignWallet(TransactionDraft $draft, User $user, int $walletId): \App\Models\TransactionLog
    {
        if (!$draft->isPending()) {
            throw new InvalidArgumentException(
                "Draft #{$draft->id} tidak dapat dimodifikasi (status: {$draft->status})."
            );
        }

        // Pastikan wallet milik user dan bukan System wallet
        $wallet = $user->wallets()
            ->where('group_type', '!=', 'System')
            ->findOrFail($walletId);

        $payload = $draft->payload;

        // Resolve sisi wallet: apakah user mengisi source atau dest?
        // Cek dari payload: apakah source_wallet_id merujuk ke System wallet bermakna
        $sourceWalletId = $payload['source_wallet_id'] ?? null;
        $sourceIsRealSystem = false;

        if ($sourceWalletId) {
            $sourceWallet = Wallet::find($sourceWalletId);
            $sourceIsRealSystem = $sourceWallet
                && $sourceWallet->group_type === 'System'
                && !str_contains(strtolower($sourceWallet->name ?? ''), 'external')
                && !str_contains(strtolower($sourceWallet->name ?? ''), 'merchant');
        }

        // Update payload dengan wallet yang dipilih user
        if ($sourceIsRealSystem) {
            // Source = System (Hutang/Piutang) → user mengisi dest (uang masuk ke wallet user)
            $payload['destination_wallet_id']   = $wallet->id;
            $payload['destination_wallet_name'] = $wallet->name;
        } else {
            // Dest = System/Merchant → user mengisi source (uang keluar dari wallet user)
            $payload['source_wallet_id']   = $wallet->id;
            $payload['source_wallet_name'] = $wallet->name;
        }

        // Wallet sudah dipilih → needs_wallet = false
        $payload['needs_wallet'] = false;
        $payload['is_cleared']   = true;

        // Simpan payload yang sudah diupdate sementara sebelum konfirmasi
        $draft->payload = $payload;

        // Langsung konfirmasi
        return $this->confirm($draft, $user);
    }

    /**
     * Batalkan draft.
     *
     * @return void
     * @throws InvalidArgumentException jika draft sudah bukan pending
     */
    public function cancel(TransactionDraft $draft, User $user): void
    {
        if ($draft->isConfirmed()) {
            throw new InvalidArgumentException(
                "Draft #{$draft->id} sudah dikonfirmasi dan tidak dapat dibatalkan."
            );
        }

        $draft->update(['status' => 'cancelled']);

        // Sinkronkan riwayat chat: tandai sebagai cancelled
        $this->syncChatHistoryAfterCancel($user->id, $draft->id);

        Log::info('DraftConfirmationService: draft dibatalkan', [
            'draft_id' => $draft->id,
            'user_id'  => $user->id,
        ]);
    }

    /**
     * Format data draft sebagai array yang kompatibel dengan response format
     * frontend (mirip formatTransactionForChat di WebChatController).
     */
    public function formatDraftForChat(TransactionDraft $draft): array
    {
        $payload = $draft->payload ?? [];

        return [
            'id'               => $draft->id,
            'is_draft'         => true,
            'reference_number' => null,
            'amount'           => $payload['amount'] ?? 0,
            'amount_formatted' => $payload['amount_formatted']
                ?? MoneyFormatter::rupiah((float) ($payload['amount'] ?? 0)),
            'is_cleared'       => false,
            'is_cancelled'     => $draft->status === 'cancelled',
            'needs_wallet'     => $payload['needs_wallet'] ?? false,
            'type_key'         => $payload['type_key'] ?? 'other',
            'category'         => $payload['category_name'] ?? null,
            'source_wallet'    => $payload['source_wallet_name'] ?? null,
            'dest_wallet'      => $payload['destination_wallet_name'] ?? null,
            'subject'          => $payload['subject'] ?? null,
            'notes'            => $payload['notes'] ?? null,
            'date'             => $payload['date'] ?? null,
            'created_at'       => $draft->created_at?->toIso8601String(),
            'expires_at'       => $draft->expires_at?->toIso8601String(),
            'ai_confidence'    => $draft->ai_confidence,
        ];
    }

    // ── Private helpers untuk sinkronisasi riwayat chat ──────────

    /**
     * Setelah draft dikonfirmasi, perbarui chat history:
     * - Ganti draft_id dengan transaction_id yang sebenarnya
     * - Tandai is_cleared = true, is_draft = false
     */
    private function syncChatHistoryAfterConfirm(int $userId, int $draftId, \App\Models\TransactionLog $transaction): void
    {
        $typeKey = match (strtolower($transaction->type?->name ?? '')) {
            'income'             => 'income',
            'expense'            => 'expense',
            'transfer'           => 'transfer',
            'debt', 'receivable' => 'debt',
            default              => 'other',
        };

        ChatMessage::whereHas('conversation', fn ($q) => $q->where('user_id', $userId))
            ->where('role', 'assistant')
            ->chunkById(100, function ($messages) use ($draftId, $transaction, $typeKey) {
                foreach ($messages as $message) {
                    $content = $message->content ?? [];
                    $changed = false;

                    foreach ($content as &$component) {
                        if (($component['type'] ?? null) !== 'transaction_card') {
                            continue;
                        }

                        $trxData = $component['transaction'] ?? [];

                        // Cocokkan berdasarkan draft_id atau id (untuk backward compat)
                        $isDraftMatch   = isset($trxData['draft_id'])
                            && (int) $trxData['draft_id'] === $draftId;
                        $isLegacyMatch  = !isset($trxData['draft_id'])
                            && isset($trxData['is_draft'])
                            && $trxData['is_draft'] === true
                            && (int) ($trxData['id'] ?? 0) === $draftId;

                        if (!$isDraftMatch && !$isLegacyMatch) {
                            continue;
                        }

                        // Update komponen ke state confirmed
                        $component['needs_wallet']                    = false;
                        $component['is_draft']                        = false;
                        $component['transaction']['id']               = $transaction->id;
                        $component['transaction']['draft_id']         = null;
                        $component['transaction']['is_draft']         = false;
                        $component['transaction']['is_cleared']       = true;
                        $component['transaction']['is_cancelled']     = false;
                        $component['transaction']['notes']            = $transaction->notes;
                        $component['transaction']['type_key']         = $typeKey;
                        $component['transaction']['source_wallet']    = $transaction->sourceWallet?->name;
                        $component['transaction']['dest_wallet']      = $transaction->destinationWallet?->name;
                        $component['transaction']['amount_formatted'] = MoneyFormatter::rupiah($transaction->amount);
                        $component['transaction']['reference_number'] = $transaction->reference_number;
                        $changed = true;
                    }
                    unset($component);

                    if ($changed) {
                        $message->forceFill(['content' => $content])->save();
                    }
                }
            });
    }

    /**
     * Setelah draft dibatalkan, perbarui chat history agar card tampil sebagai cancelled.
     */
    private function syncChatHistoryAfterCancel(int $userId, int $draftId): void
    {
        ChatMessage::whereHas('conversation', fn ($q) => $q->where('user_id', $userId))
            ->where('role', 'assistant')
            ->chunkById(100, function ($messages) use ($draftId) {
                foreach ($messages as $message) {
                    $content = $message->content ?? [];
                    $changed = false;

                    foreach ($content as &$component) {
                        if (($component['type'] ?? null) !== 'transaction_card') {
                            continue;
                        }

                        $trxData = $component['transaction'] ?? [];

                        $isDraftMatch  = isset($trxData['draft_id'])
                            && (int) $trxData['draft_id'] === $draftId;
                        $isLegacyMatch = !isset($trxData['draft_id'])
                            && isset($trxData['is_draft'])
                            && $trxData['is_draft'] === true
                            && (int) ($trxData['id'] ?? 0) === $draftId;

                        if (!$isDraftMatch && !$isLegacyMatch) {
                            continue;
                        }

                        $component['needs_wallet']                    = false;
                        $component['transaction']['is_cancelled']     = true;
                        $component['transaction']['is_cleared']       = false;
                        $changed = true;
                    }
                    unset($component);

                    if ($changed) {
                        $message->forceFill(['content' => $content])->save();
                    }
                }
            });
    }
}
