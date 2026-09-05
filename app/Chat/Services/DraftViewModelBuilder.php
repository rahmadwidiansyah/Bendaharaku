<?php

declare(strict_types=1);

namespace App\Chat\Services;

use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\Wallet;
use Illuminate\Support\Carbon;

class DraftViewModelBuilder
{
    public function buildFakeTransactionFromPayload(array $payload, ?string $missingWalletSide = null): TransactionLog
    {
        $fakeTrx = new TransactionLog;
        $fakeTrx->amount = $payload['amount'] ?? 0;
        $fakeTrx->is_cleared = false;
        // Samakan notes & description (nama barang) — sesuai request: description samain aja dengan note
        $notes = $payload['notes'] ?? $payload['description'] ?? null;
        $subject = $payload['subject'] ?? $notes ?? null;
        $fakeTrx->subject = $subject;
        $fakeTrx->notes = $notes ?? $subject;
        $fakeTrx->date = isset($payload['date'])
            ? Carbon::parse($payload['date'])
            : now();

        if (isset($payload['source_wallet_name']) || isset($payload['source_wallet_id'])) {
            $sourceWallet = new Wallet;
            $sourceWallet->id = $payload['source_wallet_id'] ?? null;
            $sourceWallet->name = $missingWalletSide === 'SOURCE' || $missingWalletSide === 'BOTH'
                ? null
                : ($payload['source_wallet_name'] ?? null);
            $sourceWallet->group_type = $this->resolveWalletGroupType($payload, 'source');
            $fakeTrx->setRelation('sourceWallet', $sourceWallet);
        }

        if (isset($payload['destination_wallet_name']) || isset($payload['destination_wallet_id'])) {
            $destWallet = new Wallet;
            $destWallet->id = $payload['destination_wallet_id'] ?? null;
            $destWallet->name = $missingWalletSide === 'DESTINATION' || $missingWalletSide === 'BOTH'
                ? null
                : ($payload['destination_wallet_name'] ?? null);
            $destWallet->group_type = $this->resolveWalletGroupType($payload, 'destination');
            $fakeTrx->setRelation('destinationWallet', $destWallet);
        }

        if (isset($payload['category_name'])) {
            $category = new Category;
            $category->id = $payload['category_id'] ?? null;
            $category->category_name = $payload['category_name'];
            $fakeTrx->setRelation('category', $category);
        }

        $typeKey = $payload['type_key'] ?? 'expense';
        $typeName = match ($typeKey) {
            'income' => 'Income',
            'expense' => 'Expense',
            'transfer' => 'Transfer',
            'debt' => 'Debt',
            'receivable' => 'Receivable',
            default => 'Expense',
        };
        $type = new TransactionType;
        $type->name = $typeName;
        $fakeTrx->setRelation('type', $type);

        return $fakeTrx;
    }

    private function resolveWalletGroupType(array $payload, string $side): string
    {
        $name = $side === 'source'
            ? ($payload['source_wallet_name'] ?? '')
            : ($payload['destination_wallet_name'] ?? '');

        $nameLower = strtolower((string) $name);

        if (str_contains($nameLower, 'external')
            || str_contains($nameLower, 'merchant')
            || str_contains($nameLower, 'system')) {
            return 'System';
        }

        return 'Liquid';
    }
}
