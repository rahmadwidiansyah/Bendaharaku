<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\ParsedTransaction;
use App\DTO\ResolvedTransaction;
use App\Enums\TransactionIntent;
use App\Models\User;
use App\Exceptions\CategoryNotFoundException;
use App\Exceptions\WalletNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class TransactionResolver
{
    /**
     * Mentranslasikan ParsedTransaction menjadi entitas ID primitif database.
     *
     * @throws CategoryNotFoundException
     * @throws WalletNotFoundException
     * @throws RuntimeException
     */
    public function resolve(User $user, ParsedTransaction $parsed): ResolvedTransaction
    {
        // 1. Validasi Kehadiran Intent Transaksi
        if ($parsed->transactionType === null) {
            throw new RuntimeException("Validasi Gagal: Intensi transaksi (TransactionIntent) tidak boleh kosong.");
        }

        // 2. Validasi Khusus Transfer
        if ($parsed->transactionType === TransactionIntent::Transfer) {
            if (blank($parsed->sourceWallet) || blank($parsed->destinationWallet)) {
                throw new WalletNotFoundException("Validasi Gagal: Transaksi 'Transfer' mewajibkan parameter dompet asal dan tujuan.");
            }
        }

        // 3. Optimalisasi Kueri N+1
        $wallets = $user->wallets()->get();
        $categories = $user->categories()->get();

        // 4. Resolusi ID Kategori
        $category = $this->searchCategory($parsed->category, $categories);

        // 5. Alokasi ID Dompet (Exhaustive Match berdasarkan Enum & Config SSOT)
        [$sourceWalletId, $destinationWalletId] = match ($parsed->transactionType) {
            TransactionIntent::Expense => [
                $this->searchWalletToken($parsed->sourceWallet, $wallets, 'Asal'),
                $this->resolveSystemWallet((string) config('bendaharaku.system_wallets.merchant'), $wallets)
            ],
            TransactionIntent::Income => [
                $this->resolveSystemWallet((string) config('bendaharaku.system_wallets.external'), $wallets),
                $this->searchWalletToken($parsed->destinationWallet ?? $parsed->sourceWallet, $wallets, 'Tujuan')
            ],
            TransactionIntent::Transfer => [
                $this->searchWalletToken($parsed->sourceWallet, $wallets, 'Asal'),
                $this->searchWalletToken($parsed->destinationWallet, $wallets, 'Tujuan')
            ],
            TransactionIntent::Debt => [
                $this->searchWalletToken($parsed->sourceWallet, $wallets, 'Asal'),
                $this->resolveSystemWallet((string) config('bendaharaku.system_wallets.debt'), $wallets)
            ],
            TransactionIntent::Receivable => [
                $this->searchWalletToken($parsed->sourceWallet, $wallets, 'Asal'),
                $this->resolveSystemWallet((string) config('bendaharaku.system_wallets.receivable'), $wallets)
            ],
        };

        return new ResolvedTransaction(
            amount: $parsed->amount,
            categoryId: $category->id,
            sourceWalletId: $sourceWalletId,
            destinationWalletId: $destinationWalletId,
            subject: $parsed->subject,
            notes: $parsed->notes,
            isCleared: $parsed->isCleared
        );
    }

    /**
     * Resolusi System Wallet murni berdasarkan Config (SSOT).
     * Tanpa fallback tebak-tebakan. Jika tidak ada, fail fast.
     */
    private function resolveSystemWallet(string $walletName, Collection $wallets): int
    {
        return $this->searchWalletToken($walletName, $wallets, 'Sistem (Auto)');
    }

    /**
     * Pencarian Regex Token multi-delimiter untuk Kategori.
     */
    private function searchCategory(?string $text, Collection $categories): \App\Models\Category
    {
        if (blank($text)) {
            throw new CategoryNotFoundException("Input kategori kosong.");
        }

        $search = strtolower(trim($text));

        $match = $categories->first(fn($c) => strtolower($c->category_name) === $search);
        if ($match) {
            return $match;
        }

        $match = $categories->first(function ($c) use ($search) {
            if (blank($c->keyword)) return false;
            $tokens = preg_split('/[,|;]+/', strtolower($c->keyword), -1, PREG_SPLIT_NO_EMPTY);
            return in_array($search, array_map('trim', $tokens), true);
        });

        if ($match) {
            return $match;
        }

        throw new CategoryNotFoundException("Kategori '{$text}' tidak terdaftar.");
    }

    /**
     * Pencarian Regex Token multi-delimiter untuk Dompet.
     */
    private function searchWalletToken(?string $text, Collection $wallets, string $context): int
    {
        if (blank($text)) {
            throw new WalletNotFoundException("Input dompet {$context} kosong.");
        }

        $search = strtolower(trim($text));

        $match = $wallets->first(fn($w) => strtolower($w->name) === $search);
        if ($match) {
            return $match->id;
        }

        $match = $wallets->first(function ($w) use ($search) {
            if (blank($w->keyword)) return false;
            $tokens = preg_split('/[,|;]+/', strtolower($w->keyword), -1, PREG_SPLIT_NO_EMPTY);
            return in_array($search, array_map('trim', $tokens), true);
        });

        if ($match) {
            return $match->id;
        }

        throw new WalletNotFoundException("Dompet {$context} '{$text}' tidak ditemukan.");
    }
}