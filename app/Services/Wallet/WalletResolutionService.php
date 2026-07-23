<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Enums\TransactionIntent;
use App\Enums\WalletSide;
use App\Exceptions\WalletNotFoundException;
use App\Models\TransactionDraft;
use App\Models\User;
use App\Models\Wallet;
use App\Support\StringUtils;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

class WalletResolutionService
{
    private const SYSTEM_WALLET_KEYS = ['merchant', 'external', 'debt', 'receivable'];

    public function resolveSystemWallet(int $userId, string $configKey): Wallet
    {
        $walletName = $this->getSystemWalletName($configKey);

        $wallet = Wallet::where('user_id', $userId)
            ->where('group_type', 'System')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($walletName)])
            ->first();

        if (! $wallet) {
            throw new WalletNotFoundException(
                "Dompet sistem untuk arus kas '{$walletName}' tidak terdeteksi. Pastikan konfigurasi system wallets sudah benar."
            );
        }

        return $wallet;
    }

    public function resolveSystemWalletId(int $userId, string $configKey): ?int
    {
        $walletName = $this->getSystemWalletName($configKey);

        return Wallet::where('user_id', $userId)
            ->where('group_type', 'System')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($walletName)])
            ->value('id');
    }

    public function resolveUserWallet(User $user, ?string $text): ?Wallet
    {
        if (blank($text)) {
            return null;
        }

        $wallets = $user->wallets()
            ->where('group_type', '!=', 'System')
            ->get();

        return StringUtils::findByNameOrKeyword($wallets, $text);
    }

    public function resolveUserWalletId(User $user, ?string $text): ?int
    {
        $wallet = $this->resolveUserWallet($user, $text);

        return $wallet?->id;
    }

    public function isSystemWallet(Wallet $wallet): bool
    {
        return $wallet->group_type === 'System';
    }

    public function isExternalWallet(Wallet $wallet): bool
    {
        if (! $this->isSystemWallet($wallet)) {
            return false;
        }

        $externalName = $this->getSystemWalletName('external');

        return mb_strtolower($wallet->name) === mb_strtolower($externalName);
    }

    public function isMerchantWallet(Wallet $wallet): bool
    {
        if (! $this->isSystemWallet($wallet)) {
            return false;
        }

        $merchantName = $this->getSystemWalletName('merchant');

        return mb_strtolower($wallet->name) === mb_strtolower($merchantName);
    }

    public function isMeaningfulSystemWallet(Wallet $wallet): bool
    {
        if (! $this->isSystemWallet($wallet)) {
            return false;
        }

        $lowerName = mb_strtolower($wallet->name);
        $externalName = mb_strtolower($this->getSystemWalletName('external'));
        $merchantName = mb_strtolower($this->getSystemWalletName('merchant'));

        return $lowerName !== $externalName && $lowerName !== $merchantName;
    }

    public function isExternalByName(string $name): bool
    {
        $externalName = $this->getSystemWalletName('external');

        return mb_strtolower($name) === mb_strtolower($externalName);
    }

    public function userWalletMentionedInText(string $text, User $user): bool
    {
        $wallets = $user->wallets()
            ->where('group_type', '!=', 'System')
            ->get(['name', 'keyword']);

        foreach ($wallets as $wallet) {
            if (StringUtils::containsKeyword($text, $wallet->name)) {
                return true;
            }
            if ($wallet->keyword && StringUtils::containsKeyword($text, $wallet->keyword)) {
                return true;
            }
        }

        return false;
    }

    public function getSystemWalletName(string $configKey): string
    {
        return (string) Config::get("bendaharaku.system_wallets.{$configKey}", '');
    }

    public function getAllSystemWalletIds(int $userId): array
    {
        $ids = [];
        foreach (self::SYSTEM_WALLET_KEYS as $key) {
            $ids[$key] = $this->resolveSystemWalletId($userId, $key);
        }

        return $ids;
    }

    /**
     * Cari wallet user dari teks menggunakan offset-based matching dengan intent-aware assignment.
     * Mengembalikan nama wallet (string), bukan ID.
     * Digunakan oleh LocalRuleEngine untuk pre-LLM parsing.
     *
     * @param  Collection<int, Wallet>  $wallets  Semua wallet user (include system)
     * @return array{sourceWallet: string|null, destinationWallet: string|null}
     */
    public function matchWalletsFromText(string $text, Collection $wallets, TransactionIntent $intent): array
    {
        $matchedWallets = [];
        $lowerText = mb_strtolower($text);

        foreach ($wallets as $wallet) {
            if ($wallet->group_type === 'System') {
                continue;
            }

            $tokens = array_values(array_filter([
                StringUtils::normalize($wallet->name),
                ...StringUtils::tokenizeKeywords($wallet->keyword),
            ]));

            foreach ($tokens as $token) {
                if ($token === '') {
                    continue;
                }
                $pos = mb_strpos($lowerText, $token);
                if ($pos !== false) {
                    $matchedWallets[] = ['wallet' => $wallet, 'token' => $token, 'offset' => $pos];
                }
            }
        }

        $uniqueMatches = [];
        foreach ($matchedWallets as $match) {
            $walletId = $match['wallet']->id;
            if (! isset($uniqueMatches[$walletId])) {
                $uniqueMatches[$walletId] = $match;
            }
        }
        $matchedWallets = array_values($uniqueMatches);
        usort($matchedWallets, fn ($a, $b) => $a['offset'] <=> $b['offset']);

        $sourceWallet = null;
        $destinationWallet = null;

        if ($intent === TransactionIntent::Transfer) {
            if (count($matchedWallets) >= 2) {
                $sourceWallet = $matchedWallets[0]['wallet']->name;
                $destinationWallet = $matchedWallets[1]['wallet']->name;
            } elseif (count($matchedWallets) === 1) {
                $name = $matchedWallets[0]['wallet']->name;
                $offset = $matchedWallets[0]['offset'];
                $prefix = substr($text, max(0, $offset - 10), 10);
                if (preg_match('/\b(ke|to)\b/i', $prefix)) {
                    $destinationWallet = $name;
                } else {
                    $sourceWallet = $name;
                }
            }
        } elseif (count($matchedWallets) >= 1) {
            $sourceWallet = $matchedWallets[0]['wallet']->name;
        }

        return ['sourceWallet' => $sourceWallet, 'destinationWallet' => $destinationWallet];
    }

    /**
     * Alokasi wallet untuk draft ketika user belum menyebutkan wallet.
     * SSOT untuk logika ini — digunakan oleh Orchestrator dan TransactionResolver.
     *
     * @return array{0: int, 1: int, 2: string} [sourceWalletId, destinationWalletId, missingWalletSide]
     */
    public function resolveDraftWalletAllocation(
        TransactionIntent $transactionType,
        int $userId,
        ?string $categoryName = null,
        ?string $systemKey = null,
    ): array {
        $externalId = $this->resolveSystemWallet($userId, 'external')->id;
        $merchantId = $this->resolveSystemWallet($userId, 'merchant')->id;
        $debtId = $this->resolveSystemWallet($userId, 'debt')->id;
        $receivableId = $this->resolveSystemWallet($userId, 'receivable')->id;

        if ($systemKey !== null) {
            $isReceivableReturn = $transactionType === TransactionIntent::Receivable
                && $systemKey === 'RECEIVABLE_PAYMENT';
            $isDebtReceive = $transactionType === TransactionIntent::Debt
                && $systemKey === 'LOAN';
            $isDebtPay = $transactionType === TransactionIntent::Debt
                && $systemKey === 'DEBT_PAYMENT';
        } elseif ($categoryName !== null) {
            $lower = mb_strtolower($categoryName);
            $isReceivableReturn = $transactionType === TransactionIntent::Receivable
                && (str_contains($lower, 'terima') || str_contains($lower, 'bayar') || str_contains($lower, 'kembali'));
            $isDebtReceive = $transactionType === TransactionIntent::Debt
                && (str_contains($lower, 'dapat') || str_contains($lower, 'terima') || str_contains($lower, 'pinjam'));
            $isDebtPay = $transactionType === TransactionIntent::Debt && ! $isDebtReceive;
        } else {
            $isReceivableReturn = false;
            $isDebtReceive = false;
            $isDebtPay = false;
        }

        return match (true) {
            $isReceivableReturn => [$receivableId, $externalId, WalletSide::Destination->value],
            $transactionType === TransactionIntent::Receivable => [$externalId, $receivableId, WalletSide::Source->value],
            $isDebtPay => [$externalId, $debtId, WalletSide::Source->value],
            $transactionType === TransactionIntent::Debt && $isDebtReceive => [$debtId, $externalId, WalletSide::Destination->value],
            $transactionType === TransactionIntent::Debt => [$externalId, $debtId, WalletSide::Source->value],
            $transactionType === TransactionIntent::Income => [$externalId, $merchantId, WalletSide::Destination->value],
            default => [$externalId, $merchantId, WalletSide::Source->value],
        };
    }
}
