<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\ParsedTransaction;
use App\DTO\ResolvedTransaction;
use App\Enums\TransactionIntent;
use App\Exceptions\CategoryNotFoundException;
use App\Exceptions\WalletNotFoundException;
use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Category\CategoryResolutionService;
use App\Services\Wallet\WalletResolutionService;
use App\Support\StringUtils;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class TransactionResolver
{
    public function __construct(
        private readonly WalletResolutionService $walletResolution,
        private readonly CategoryResolutionService $categoryResolution,
    ) {}
    /**
     * Mentranslasikan ParsedTransaction menjadi entitas ID primitif database.
     *
     * Mendukung ALL_BALANCE intent: jika parsed->useAllBalance = true,
     * backend mengambil saldo aktual wallet sumber sebagai amount.
     * Backend adalah source of truth untuk saldo — AI hanya memahami intent.
     *
     * @throws CategoryNotFoundException
     * @throws WalletNotFoundException
     * @throws RuntimeException
     */
    public function resolve(User $user, ParsedTransaction $parsed): ResolvedTransaction
    {
        // 1. Validasi Kehadiran Intent Transaksi
        if ($parsed->transactionType === null) {
            throw new RuntimeException('Validasi Gagal: Intensi transaksi (TransactionIntent) tidak boleh kosong.');
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

        // 5. Alokasi ID Dompet — delegasi ke WalletResolutionService (SSOT)
        $userId = $user->id;
        $systemKey = $category->system_key;

        if ($parsed->transactionType === TransactionIntent::Transfer) {
            if (blank($parsed->sourceWallet) || blank($parsed->destinationWallet)) {
                throw new WalletNotFoundException("Validasi Gagal: Transaksi 'Transfer' mewajibkan parameter dompet asal dan tujuan.");
            }
            $sourceWalletId = $this->searchWalletToken($parsed->sourceWallet, $wallets, 'Asal');
            $destinationWalletId = $this->searchWalletToken($parsed->destinationWallet, $wallets, 'Tujuan');
        } else {
            [$systemSourceId, $systemDestId] = $this->walletResolution->resolveDraftWalletAllocation(
                transactionType: $parsed->transactionType ?? TransactionIntent::Expense,
                userId: $userId,
                categoryName: $category->category_name,
                systemKey: $systemKey,
            );

            // Di mana user wallet disebutkan? System wallet dipasangkan dengan user wallet.
            $sourceWalletId = $parsed->sourceWallet !== null
                && $this->walletResolution->resolveUserWalletId($user, $parsed->sourceWallet) !== null
                ? $this->searchWalletToken($parsed->sourceWallet, $wallets, 'Asal')
                : $systemSourceId;

            $destinationWalletId = $parsed->destinationWallet !== null
                && $this->walletResolution->resolveUserWalletId($user, $parsed->destinationWallet) !== null
                ? $this->searchWalletToken($parsed->destinationWallet, $wallets, 'Tujuan')
                : $systemDestId;

            // Fallback: jika kedua wallet tidak disebutkan user, gunakan system wallet untuk yang belum diisi
            // Ini terjadi ketika AI hanya mendeteksi satu sisi wallet
            if ($sourceWalletId === $systemSourceId && $destinationWalletId === $systemDestId) {
                // Keduanya system → cek apakah user menyebut wallet di source atau dest
                if ($parsed->sourceWallet !== null) {
                    $sourceWalletId = $this->searchWalletToken($parsed->sourceWallet, $wallets, 'Asal');
                } elseif ($parsed->destinationWallet !== null) {
                    $destinationWalletId = $this->searchWalletToken($parsed->destinationWallet, $wallets, 'Tujuan');
                }
            }
        }

        // 6. Resolusi Amount — Handle ALL_BALANCE intent
        //
        // Jika AI mengembalikan use_all_balance=true (perintah "pindahkan semua saldo"),
        // backend mengambil saldo aktual wallet sumber sebagai amount.
        // Ini memastikan backend adalah source of truth untuk nominal saldo.
        $amount = $parsed->amount;
        if ($parsed->useAllBalance) {
            $amount = $this->resolveAllBalance($sourceWalletId, $wallets);
        }

        // Validasi final amount
        if ($amount <= 0) {
            if ($parsed->useAllBalance) {
                $sourceWalletName = $wallets->firstWhere('id', $sourceWalletId)?->name ?? 'wallet';
                throw new RuntimeException(
                    "Transfer seluruh saldo gagal: saldo wallet '{$sourceWalletName}' adalah 0 atau kosong."
                );
            }
            throw new RuntimeException('Validasi Gagal: Nominal transaksi harus lebih besar dari nol.');
        }

        return new ResolvedTransaction(
            amount: $amount,
            categoryId: $category->id,
            sourceWalletId: $sourceWalletId,
            destinationWalletId: $destinationWalletId,
            subject: $parsed->subject,
            notes: $parsed->notes,
            isCleared: $parsed->isCleared
        );
    }

    /**
     * Resolve amount berdasarkan saldo aktual wallet sumber.
     * Backend adalah source of truth — tidak mengandalkan nilai yang dikirim AI.
     *
     * @throws RuntimeException jika saldo wallet tidak valid
     */
    private function resolveAllBalance(int $sourceWalletId, Collection $wallets): float
    {
        $wallet = $wallets->firstWhere('id', $sourceWalletId);

        if (! $wallet) {
            throw new RuntimeException('Wallet sumber tidak ditemukan saat resolve ALL_BALANCE.');
        }

        $balance = (float) $wallet->balance;

        if ($balance <= 0) {
            throw new RuntimeException(
                "Transfer seluruh saldo gagal: saldo wallet '{$wallet->name}' adalah Rp 0 atau negatif."
            );
        }

        return $balance;
    }

    private function searchCategory(?string $text, Collection $categories): Category
    {
        $category = $this->categoryResolution->resolveByName($text, $categories);
        if ($category !== null) {
            return $category;
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

        $match = StringUtils::findByNameOrKeyword($wallets, $text);
        if ($match !== null) {
            return $match->id;
        }

        throw new WalletNotFoundException("Dompet {$context} '{$text}' tidak ditemukan.");
    }
}
