<?php

declare(strict_types=1);

namespace App\Evidence\Resolver;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * TransferResolver — Deteksi transfer internal (wallet-to-wallet milik user).
 *
 * Jika destination_account atau destination_name cocok dengan wallet milik user,
 * maka ini adalah internal transfer.
 */
class TransferResolver
{
    public function __construct(
        private WalletResolver $walletResolver,
    ) {}

    /**
     * Deteksi apakah ini transfer internal.
     *
     * @return array{is_internal: bool, destination_wallet_id: int|null, destination_wallet_name: string|null, transaction_type: string, confidence: float}
     */
    public function resolve(
        User $user,
        ?string $destinationAccount,
        ?string $destinationName,
        ?string $transactionType,
    ): array {
        // Hanya proses jika transaction type = TRANSFER
        if (strtolower($transactionType ?? '') !== 'transfer') {
            return [
                'is_internal' => false,
                'destination_wallet_id' => null,
                'destination_wallet_name' => null,
                'transaction_type' => $transactionType ?? 'TRANSFER',
                'confidence' => 0.5,
            ];
        }

        // Coba resolve destination ke wallet milik user
        $destination = $this->walletResolver->resolveDestination($user, $destinationAccount, $destinationName);

        if ($destination['wallet_id'] !== null) {
            Log::info('Internal transfer detected', [
                'destination_wallet_id' => $destination['wallet_id'],
                'destination_wallet_name' => $destination['wallet_name'],
            ]);

            return [
                'is_internal' => true,
                'destination_wallet_id' => $destination['wallet_id'],
                'destination_wallet_name' => $destination['wallet_name'],
                'transaction_type' => 'INTERNAL_TRANSFER',
                'confidence' => $destination['confidence'],
            ];
        }

        return [
            'is_internal' => false,
            'destination_wallet_id' => null,
            'destination_wallet_name' => $destinationName,
            'transaction_type' => 'TRANSFER',
            'confidence' => 0.5,
        ];
    }
}
