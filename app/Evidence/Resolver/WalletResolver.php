<?php

declare(strict_types=1);

namespace App\Evidence\Resolver;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

/**
 * WalletResolver — Match wallet dari EvidenceData ke wallet milik user.
 *
 * Matching strategy:
 * 1. account_number (exact)
 * 2. account_name (fuzzy — Levenshtein)
 * 3. wallet provider + name (keyword)
 * 4. name similarity
 */
class WalletResolver
{
    /**
     * Resolve source wallet berdasarkan wallet_name dan bank_name.
     *
     * @return array{wallet_id: int|null, wallet_name: string|null, confidence: float, match_method: string|null}
     */
    public function resolveSource(User $user, ?string $walletName, ?string $bankName): array
    {
        if (! $walletName && ! $bankName) {
            return ['wallet_id' => null, 'wallet_name' => null, 'confidence' => 0.0, 'match_method' => null];
        }

        $wallets = Wallet::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($wallets->isEmpty()) {
            return ['wallet_id' => null, 'wallet_name' => $walletName, 'confidence' => 0.0, 'match_method' => null];
        }

        // Strategy 1: Exact name match
        $searchName = strtolower($walletName ?? $bankName ?? '');
        foreach ($wallets as $wallet) {
            if (strtolower($wallet->name) === $searchName) {
                Log::info('Wallet matched by exact name', ['wallet_id' => $wallet->id, 'name' => $wallet->name]);

                return ['wallet_id' => $wallet->id, 'wallet_name' => $wallet->name, 'confidence' => 1.0, 'match_method' => 'exact_name'];
            }
        }

        // Strategy 2: Keyword match
        foreach ($wallets as $wallet) {
            if ($wallet->keyword && str_contains($searchName, strtolower($wallet->keyword))) {
                Log::info('Wallet matched by keyword', ['wallet_id' => $wallet->id, 'keyword' => $wallet->keyword]);

                return ['wallet_id' => $wallet->id, 'wallet_name' => $wallet->name, 'confidence' => 0.90, 'match_method' => 'keyword'];
            }
        }

        // Strategy 3: Bank code match
        if ($bankName) {
            $searchBank = strtolower($bankName);
            foreach ($wallets as $wallet) {
                if ($wallet->bank_code && strtolower($wallet->bank_code) === $searchBank) {
                    Log::info('Wallet matched by bank code', ['wallet_id' => $wallet->id, 'bank_code' => $wallet->bank_code]);

                    return ['wallet_id' => $wallet->id, 'wallet_name' => $wallet->name, 'confidence' => 0.85, 'match_method' => 'bank_code'];
                }
            }
        }

        // Strategy 4: Fuzzy name match (Levenshtein)
        $bestMatch = null;
        $bestScore = 0;
        foreach ($wallets as $wallet) {
            $distance = levenshtein(strtolower($wallet->name), $searchName);
            $maxLen = max(strlen($wallet->name), strlen($searchName));
            $score = $maxLen > 0 ? 1 - ($distance / $maxLen) : 0;

            if ($score > $bestScore && $score >= 0.6) {
                $bestScore = $score;
                $bestMatch = $wallet;
            }
        }

        if ($bestMatch) {
            Log::info('Wallet matched by fuzzy name', [
                'wallet_id' => $bestMatch->id,
                'name' => $bestMatch->name,
                'score' => round($bestScore, 2),
            ]);

            return [
                'wallet_id' => $bestMatch->id,
                'wallet_name' => $bestMatch->name,
                'confidence' => round($bestScore * 0.8, 4),
                'match_method' => 'fuzzy_name',
            ];
        }

        return ['wallet_id' => null, 'wallet_name' => $walletName, 'confidence' => 0.0, 'match_method' => null];
    }

    /**
     * Resolve destination wallet berdasarkan destination_account dan destination_name.
     *
     * @return array{wallet_id: int|null, wallet_name: string|null, confidence: float, match_method: string|null}
     */
    public function resolveDestination(User $user, ?string $destinationAccount, ?string $destinationName): array
    {
        if (! $destinationAccount && ! $destinationName) {
            return ['wallet_id' => null, 'wallet_name' => null, 'confidence' => 0.0, 'match_method' => null];
        }

        $wallets = Wallet::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($wallets->isEmpty()) {
            return ['wallet_id' => null, 'wallet_name' => null, 'confidence' => 0.0, 'match_method' => null];
        }

        // Strategy 1: Account number match
        if ($destinationAccount) {
            $cleanAccount = preg_replace('/\s+/', '', $destinationAccount);
            foreach ($wallets as $wallet) {
                if ($wallet->account_number && preg_replace('/\s+/', '', $wallet->account_number) === $cleanAccount) {
                    Log::info('Destination wallet matched by account number', ['wallet_id' => $wallet->id]);

                    return ['wallet_id' => $wallet->id, 'wallet_name' => $wallet->name, 'confidence' => 1.0, 'match_method' => 'account_number'];
                }
            }
        }

        // Strategy 2: Exact name match
        $searchName = strtolower($destinationAccount ?? $destinationName ?? '');
        if ($searchName) {
            foreach ($wallets as $wallet) {
                if (strtolower($wallet->name) === $searchName) {
                    Log::info('Destination wallet matched by exact name', ['wallet_id' => $wallet->id, 'name' => $wallet->name]);

                    return ['wallet_id' => $wallet->id, 'wallet_name' => $wallet->name, 'confidence' => 1.0, 'match_method' => 'exact_name'];
                }
            }
        }

        // Strategy 3: Account name match (Levenshtein)
        if ($destinationName) {
            $searchName = strtolower($destinationName);
            foreach ($wallets as $wallet) {
                if ($wallet->account_name && levenshtein(strtolower($wallet->account_name), $searchName) <= 3) {
                    Log::info('Destination wallet matched by account name', ['wallet_id' => $wallet->id]);

                    return ['wallet_id' => $wallet->id, 'wallet_name' => $wallet->name, 'confidence' => 0.85, 'match_method' => 'account_name'];
                }
            }
        }

        // Strategy 4: Name similarity
        $similarityName = $destinationName ?? $destinationAccount;
        if ($similarityName) {
            $searchName = strtolower($similarityName);
            foreach ($wallets as $wallet) {
                if (str_contains(strtolower($wallet->name), $searchName) || str_contains($searchName, strtolower($wallet->name))) {
                    Log::info('Destination wallet matched by name similarity', ['wallet_id' => $wallet->id]);

                    return ['wallet_id' => $wallet->id, 'wallet_name' => $wallet->name, 'confidence' => 0.70, 'match_method' => 'name_similarity'];
                }
            }
        }

        return ['wallet_id' => null, 'wallet_name' => $destinationName, 'confidence' => 0.0, 'match_method' => null];
    }
}
