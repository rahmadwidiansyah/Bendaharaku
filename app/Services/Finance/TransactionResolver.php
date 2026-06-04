<?php

namespace App\Services\Finance;

use App\Models\Wallet;
use App\Models\Category;
use App\Models\TransactionType;
use Exception;

class TransactionResolver
{
    /**
     * Memetakan nama/keyword teks menjadi ID database riil dengan proteksi user_id yang ketat.
     */
    public function resolve(int $userId, string $type, string $categoryKey, string $sourceKey, string $destKey): array
    {
        // 1. Resolve Tipe Transaksi
        $transactionType = TransactionType::where('name', $type)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)->orWhereNull('user_id');
            })->first();

        if (!$transactionType) {
            throw new Exception("Tipe transaksi [{$type}] tidak terdaftar di sistem.");
        }

        // 2. Resolve Dompet Sumber (Strict User Scoping)
        $sourceWallet = Wallet::where('user_id', $userId)
            ->where(function ($query) use ($sourceKey) {
                $query->where('name', $sourceKey)
                      ->orWhereRaw('LOWER(keyword) LIKE ?', ['%' . strtolower($sourceKey) . '%']);
            })->first();

        if (!$sourceWallet) {
            throw new Exception("Dompet asal [{$sourceKey}] tidak ditemukan atau bukan milik Anda.");
        }

        // 3. Resolve Dompet Tujuan (Strict User Scoping)
        $destWallet = Wallet::where('user_id', $userId)
            ->where(function ($query) use ($destKey) {
                $query->where('name', $destKey)
                      ->orWhereRaw('LOWER(keyword) LIKE ?', ['%' . strtolower($destKey) . '%']);
            })->first();

        if (!$destWallet) {
            throw new Exception("Dompet tujuan [{$destKey}] tidak ditemukan atau bukan milik Anda.");
        }

        // 4. Resolve Kategori Berdasarkan Tipe (Strict User Scoping)
        $category = Category::where('user_id', $userId)
            ->where('type_id', $transactionType->id)
            ->where(function ($query) use ($categoryKey) {
                $query->where('category_name', $categoryKey)
                      ->orWhereRaw('LOWER(keyword) LIKE ?', ['%' . strtolower($categoryKey) . '%']);
            })->first();

        if (!$category) {
            throw new Exception("Kategori [{$categoryKey}] tidak cocok untuk tipe transaksi {$type}.");
        }

        return [
            'type_id'               => $transactionType->id,
            'source_wallet_id'      => $sourceWallet->id,
            'destination_wallet_id' => $destWallet->id,
            'category_id'           => $category->id,
        ];
    }
}