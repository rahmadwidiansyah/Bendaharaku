<?php

namespace App\Actions;

use App\DTO\ParsedTransaction;
use App\Models\TransactionLog;
use App\Models\Wallet;
use App\Services\Finance\TransactionResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessTransactionAction
{
    public function __construct(
        private TransactionResolver $resolver
    ) {}

    /**
     * Mengorkestrasi resolusi string data ke database ID dan memproses mutasi keuangan.
     */
    public function execute(ParsedTransaction $dto): TransactionLog
    {
        return DB::transaction(function () use ($dto) {
            
            // Resolusi string/keyword murni menjadi ID riil yang aman
            $resolved = $this->resolver->resolve(
                $dto->userId,
                $dto->transactionType,
                $dto->categoryKeyword,
                $dto->sourceWalletKeyword,
                $dto->destinationWalletKeyword
            );

            // Ambil data Model Wallet dengan penguncian scope user_id demi aspek integritas
            $source = Wallet::where('user_id', $dto->userId)->findOrFail($resolved['source_wallet_id']);
            $destination = Wallet::where('user_id', $dto->userId)->findOrFail($resolved['destination_wallet_id']);

            $mainWallet = ($source->group_type !== 'System') ? $source : $destination;
            $balanceBefore = $mainWallet->balance;

            if ($dto->isCleared) {
                // Mutasi nilai saldo riil
                $source->decrement('balance', $dto->amount);
                $destination->increment('balance', $dto->amount);
                
                // Ambil ulang saldo terbaru dari database demi akurasi log
                $balanceAfter = Wallet::where('user_id', $dto->userId)->where('id', $mainWallet->id)->value('balance');
            } else {
                $balanceAfter = $balanceBefore;
            }

            $refNumber = $dto->sourceType === 'TELEGRAM'
                ? 'TEL' . date('YmdHis') . rand(100, 999)
                : 'TRX-' . strtoupper(Str::random(10));

            return TransactionLog::create([
                'reference_number'      => $refNumber,
                'user_id'               => $dto->userId,
                'date'                  => $dto->date ?? now()->format('Y-m-d'),
                'type_id'               => $resolved['type_id'],
                'category_id'           => $resolved['category_id'],
                'source_wallet_id'      => $resolved['source_wallet_id'],
                'destination_wallet_id' => $resolved['destination_wallet_id'],
                'amount'                => $dto->amount,
                'balance_before'        => $balanceBefore,
                'balance_after'         => $balanceAfter,
                'subject'               => $dto->subject,
                'notes'                 => $dto->notes,
                'is_cleared'            => $dto->isCleared,
                'due_date'              => $dto->dueDate,
                'due_date_type'         => $dto->dueDateType,
                'due_date_interval'     => $dto->dueDateInterval,
            ]);
        });
    }
}