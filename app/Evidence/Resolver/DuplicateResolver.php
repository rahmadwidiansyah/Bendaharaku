<?php

declare(strict_types=1);

namespace App\Evidence\Resolver;

use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * DuplicateResolver — Deteksi kemungkinan duplikat transaksi.
 *
 * Mencari transaksi dengan:
 * - reference_number yang sama, ATAU
 * - amount + wallet + tanggal ±5 menit
 */
class DuplicateResolver
{
    /**
     * Cek apakah ada kemungkinan duplikat.
     *
     * @return array{is_duplicate: bool, confidence: float, warnings: array<int, string>, match_ids: array<int, int>}
     */
    public function resolve(
        User $user,
        ?string $referenceNumber,
        ?float $amount,
        ?int $walletId,
        ?string $transactionDate,
    ): array {
        $warnings = [];
        $matchIds = [];
        $isDuplicate = false;

        // Strategy 1: Reference number match
        if ($referenceNumber) {
            $existingByRef = TransactionLog::where('user_id', $user->id)
                ->where('reference_number', $referenceNumber)
                ->where('is_cleared', true)
                ->first();

            if ($existingByRef) {
                $warnings[] = "Duplikat ditemukan: referensi {$referenceNumber} sudah ada (transaksi #{$existingByRef->id})";
                $matchIds[] = $existingByRef->id;
                $isDuplicate = true;

                Log::warning('Duplicate found by reference number', [
                    'user_id' => $user->id,
                    'reference_number' => $referenceNumber,
                    'existing_id' => $existingByRef->id,
                ]);
            }
        }

        // Strategy 2: Amount + wallet + date ±5 minutes
        if ($amount && $walletId && $transactionDate) {
            try {
                $date = Carbon::parse($transactionDate);
                $windowStart = $date->copy()->subMinutes(5);
                $windowEnd = $date->copy()->addMinutes(5);

                $existingByAmount = TransactionLog::where('user_id', $user->id)
                    ->where('amount', $amount)
                    ->where(function ($q) use ($walletId) {
                        $q->where('source_wallet_id', $walletId)
                            ->orWhere('destination_wallet_id', $walletId);
                    })
                    ->where('is_cleared', true)
                    ->whereBetween('date', [$windowStart, $windowEnd])
                    ->first();

                if ($existingByAmount) {
                    $warnings[] = 'Kemungkinan duplikat: nominal Rp'.number_format($amount, 0, ',', '.')." dalam ±5 menit (transaksi #{$existingByAmount->id})";

                    if (! in_array($existingByAmount->id, $matchIds)) {
                        $matchIds[] = $existingByAmount->id;
                    }
                    $isDuplicate = true;

                    Log::warning('Duplicate found by amount + wallet + date', [
                        'user_id' => $user->id,
                        'amount' => $amount,
                        'existing_id' => $existingByAmount->id,
                    ]);
                }
            } catch (\Throwable) {
                // Date parsing gagal — skip strategy ini
            }
        }

        return [
            'is_duplicate' => $isDuplicate,
            'confidence' => $isDuplicate ? 0.90 : 1.0,
            'warnings' => $warnings,
            'match_ids' => $matchIds,
        ];
    }
}
