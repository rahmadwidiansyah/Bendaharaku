<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\ParsedTransaction;
use App\DTO\ResolvedTransaction;
use App\Enums\TransactionIntent;
use App\Models\User;
use App\Models\Wallet;
use App\Exceptions\CategoryNotFoundException;
use App\Exceptions\WalletNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class TransactionResolver
{
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
            throw new RuntimeException("Validasi Gagal: Intensi transaksi (TransactionIntent) tidak boleh kosong.");
        }

        // 2. Validasi Khusus Transfer
        if ($parsed->transactionType === TransactionIntent::Transfer) {
            if (blank($parsed->sourceWallet) || blank($parsed->destinationWallet)) {
                throw new WalletNotFoundException("Validasi Gagal: Transaksi 'Transfer' mewajibkan parameter dompet asal dan tujuan.");
            }
        }

        // 3. Optimalisasi Kueri N+1
        $wallets    = $user->wallets()->get();
        $categories = $user->categories()->get();

        // 4. Resolusi ID Kategori
        $category = $this->searchCategory($parsed->category, $categories);

        // 5. Alokasi ID Dompet (Exhaustive Match berdasarkan Enum & Config SSOT)
        //
        // Untuk Debt dan Receivable, ada dua arah berbeda yang ditentukan oleh kategori:
        //   Debt "Dapat Hutangan"   : System Hutang  → wallet user (uang masuk, hutang bertambah)
        //   Debt "Bayar Hutang"     : wallet user    → System Hutang (uang keluar, hutang berkurang)
        //   Receivable "Ngasih Piutang"     : wallet user    → System Piutang (uang keluar, piutang bertambah)
        //   Receivable "Terima Bayar Piutang": System Piutang → wallet user (uang masuk, piutang berkurang)
        //
        // AI sudah memilih kategori yang tepat — kita cukup periksa nama kategori untuk arah.
        $systemKey = $category->system_key;
        $categoryName = mb_strtolower($category->category_name ?? '');

        if ($systemKey !== null) {
            $isReceivableReturn = $parsed->transactionType === TransactionIntent::Receivable
                && $systemKey === 'RECEIVABLE_PAYMENT';
            $isDebtReceive = $parsed->transactionType === TransactionIntent::Debt
                && $systemKey === 'LOAN';
            $isDebtPay = $parsed->transactionType === TransactionIntent::Debt
                && $systemKey === 'DEBT_PAYMENT';
        } else {
            $isReceivableReturn = $parsed->transactionType === TransactionIntent::Receivable
                && (str_contains($categoryName, 'terima') || str_contains($categoryName, 'bayar') || str_contains($categoryName, 'kembali'));
            $isDebtReceive = $parsed->transactionType === TransactionIntent::Debt
                && (str_contains($categoryName, 'dapat') || str_contains($categoryName, 'terima') || str_contains($categoryName, 'pinjam'));
            $isDebtPay = $parsed->transactionType === TransactionIntent::Debt
                && !$isDebtReceive;
        }

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
            TransactionIntent::Debt => $isDebtReceive
                // Terima hutang: System Hutang → wallet user
                ? [
                    $this->resolveSystemWallet((string) config('bendaharaku.system_wallets.debt'), $wallets),
                    $this->searchWalletToken($parsed->destinationWallet ?? $parsed->sourceWallet, $wallets, 'Tujuan'),
                ]
                // Bayar hutang: wallet user → System Hutang
                : [
                    $this->searchWalletToken($parsed->sourceWallet, $wallets, 'Asal'),
                    $this->resolveSystemWallet((string) config('bendaharaku.system_wallets.debt'), $wallets),
                ],
            TransactionIntent::Receivable => $isReceivableReturn
                // Terima bayar piutang: System Piutang → wallet user
                ? [
                    $this->resolveSystemWallet((string) config('bendaharaku.system_wallets.receivable'), $wallets),
                    $this->searchWalletToken($parsed->destinationWallet ?? $parsed->sourceWallet, $wallets, 'Tujuan'),
                ]
                // Ngasih piutang: wallet user → System Piutang
                : [
                    $this->searchWalletToken($parsed->sourceWallet, $wallets, 'Asal'),
                    $this->resolveSystemWallet((string) config('bendaharaku.system_wallets.receivable'), $wallets),
                ],
        };

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
            throw new RuntimeException("Validasi Gagal: Nominal transaksi harus lebih besar dari nol.");
        }

        return new ResolvedTransaction(
            amount:              $amount,
            categoryId:          $category->id,
            sourceWalletId:      $sourceWalletId,
            destinationWalletId: $destinationWalletId,
            subject:             $parsed->subject,
            notes:               $parsed->notes,
            isCleared:           $parsed->isCleared
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

        if (!$wallet) {
            throw new RuntimeException("Wallet sumber tidak ditemukan saat resolve ALL_BALANCE.");
        }

        $balance = (float) $wallet->balance;

        if ($balance <= 0) {
            throw new RuntimeException(
                "Transfer seluruh saldo gagal: saldo wallet '{$wallet->name}' adalah Rp 0 atau negatif."
            );
        }

        return $balance;
    }

    /**
     * Resolusi System Wallet murni berdasarkan Config (SSOT).
     * Tanpa fallback tebak-tebakan. Jika tidak ada, fail fast.
     */
    private function resolveSystemWallet(string $walletName, Collection $wallets): int
    {
        $match = $wallets->first(fn($w) => strtolower($w->name) === strtolower(trim($walletName)));

        if (!$match) {
            throw new WalletNotFoundException(
                "Dompet sistem untuk arus kas '{$walletName}' tidak terdeteksi. Pastikan konfigurasi system wallets sudah benar."
            );
        }

        return $match->id;
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
