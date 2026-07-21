<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\User;
use App\Models\Category;
use App\Models\Wallet;
use App\DTO\AIParseResult;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use Illuminate\Support\Facades\Log;

class LocalRuleEngine
{
    /**
     * Try to parse transaction using regex and keyword rules.
     * Returns AIParseResult on success, or null on failure.
     */
    public function parse(User $user, string $text): ?AIParseResult
    {
        $normalizedText = trim($text);
        if ($normalizedText === '') {
            return null;
        }

        // 1. Fetch Categories & Wallets to match against
        $categories = Category::where('user_id', $user->id)->with('type')->get();
        $wallets = Wallet::where('user_id', $user->id)->get();

        // 2. Extract Amount
        $amountData = $this->extractAmount($normalizedText);
        if ($amountData === null) {
            return null; // Cannot parse if no amount or "all balance" intent is found
        }

        $amount = $amountData['amount'];
        $useAllBalance = $amountData['useAllBalance'];

        // 3. Match Category
        $categoryMatch = $this->matchCategory($normalizedText, $categories);
        if ($categoryMatch === null) {
            return null; // Cannot determine category
        }

        // 4. Resolve Transaction Intent
        $intent = $this->resolveIntent($categoryMatch);
        if ($intent === null) {
            return null;
        }

        // 5. Match Wallets
        $walletData = $this->matchWallets($normalizedText, $wallets, $intent);

        // 6. Extract Subject for Debt/Receivable
        $subject = $this->extractSubject($normalizedText, $intent);
        if (in_array($intent, [TransactionIntent::Debt, TransactionIntent::Receivable]) && $subject === null) {
            // Debt and receivable require a subject
            return null;
        }

        // If it's a Transfer and we couldn't match a destination wallet, it might need to go to draft.
        // We'll set isCleared to false so it goes to draft nicely if wallets are missing.
        $hasRequiredWallets = true;
        if ($intent === TransactionIntent::Transfer) {
            if ($walletData['sourceWallet'] === null || $walletData['destinationWallet'] === null) {
                $hasRequiredWallets = false;
            }
        } else {
            if ($walletData['sourceWallet'] === null) {
                $hasRequiredWallets = false;
            }
        }

        $isCleared = $hasRequiredWallets && ($subject !== null || !in_array($intent, [TransactionIntent::Debt, TransactionIntent::Receivable]));

        // Construct ParsedTransaction
        $parsedTransaction = new ParsedTransaction(
            amount:             $amount,
            transactionType:    $intent,
            category:           $categoryMatch->category_name,
            sourceWallet:       $walletData['sourceWallet'],
            destinationWallet:  $walletData['destinationWallet'],
            subject:            $subject,
            notes:              $text,
            isCleared:          $isCleared,
            useAllBalance:      $useAllBalance
        );

        Log::info('LocalRuleEngine: successfully parsed intent locally', [
            'user_id' => $user->id,
            'text' => $text,
            'intent' => $intent->value,
            'category' => $categoryMatch->category_name,
            'amount' => $amount,
            'source' => $walletData['sourceWallet'],
            'dest' => $walletData['destinationWallet'],
            'subject' => $subject,
            'use_all_balance' => $useAllBalance
        ]);

        return new AIParseResult(
            success:     true,
            confidence:  1.0, // Rule engine matches are 100% confident
            error:       null,
            transaction: $parsedTransaction,
            usage:       ['prompt' => 0, 'completion' => 0, 'total' => 0],
            provider:    'local-rules',
            model:       'regex'
        );
    }

    /**
     * Extract amount and check for all balance intent.
     */
    private function extractAmount(string $text): ?array
    {
        $lowerText = mb_strtolower($text);

        // Check for "semua saldo" / "seluruh saldo"
        $useAllBalance = false;
        if (str_contains($lowerText, 'semua saldo') || str_contains($lowerText, 'seluruh saldo') || str_contains($lowerText, 'transfer semua') || str_contains($lowerText, 'pindah semua')) {
            $useAllBalance = true;
        }

        // Regex pattern to extract digits with suffix (k, rb, ribu, jt, juta)
        // matches things like: 20 ribu, 20ribu, 20k, 20.000, 20,000, 1.5 juta, 1,5 jt
        $pattern = '/(\d+(?:[.,]\d+)?)\s*(ribu|rb|k|juta|jt)?/i';
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            // Find the match that looks like the principal amount. Usually the first one or the one with a suffix/size.
            foreach ($matches as $match) {
                $rawVal = str_replace(',', '.', $match[1]);
                $val = (float) $rawVal;
                $suffix = isset($match[2]) ? strtolower($match[2]) : '';

                if ($suffix === 'k' || $suffix === 'rb' || $suffix === 'ribu') {
                    $val *= 1000;
                } elseif ($suffix === 'jt' || $suffix === 'juta') {
                    $val *= 1000000;
                }

                if ($val > 0) {
                    return [
                        'amount' => $val,
                        'useAllBalance' => $useAllBalance
                    ];
                }
            }
        }

        if ($useAllBalance) {
            return [
                'amount' => 0.0,
                'useAllBalance' => true
            ];
        }

        return null;
    }

    /**
     * Match Category using longest token match logic with substring support.
     */
    private function matchCategory(string $text, $categories): ?Category
    {
        $matchedCategories = [];
        $lowerText = mb_strtolower($text);

        foreach ($categories as $category) {
            $tokens = array_filter([
                $category->category_name,
                ...preg_split('/[,|;]+/', (string) $category->keyword, -1, PREG_SPLIT_NO_EMPTY)
            ]);

            foreach ($tokens as $token) {
                $token = trim(mb_strtolower($token));
                if ($token === '') continue;

                // Flexible substring check
                if (str_contains($lowerText, $token)) {
                    $matchedCategories[] = [
                        'category' => $category,
                        'token' => $token,
                        'length' => strlen($token)
                    ];
                }
            }
        }

        if (empty($matchedCategories)) {
            return null;
        }

        // Sort by matching token length descending (longest wins)
        usort($matchedCategories, fn($a, $b) => $b['length'] <=> $a['length']);

        // Disambiguate if multiple matches have the same longest length
        if (count($matchedCategories) >= 2 && $matchedCategories[0]['length'] === $matchedCategories[1]['length']) {
            $bestCategory = $matchedCategories[0]['category'];
            $secondCategory = $matchedCategories[1]['category'];
            $bestKey = $bestCategory->system_key;
            $secondKey = $secondCategory->system_key;

            $keys = [$bestKey, $secondKey];
            if (in_array('DEBT_PAYMENT', $keys) && in_array('RECEIVABLE_PAYMENT', $keys)) {
                if (str_contains($lowerText, 'balikin uang')) {
                    return $bestKey === 'RECEIVABLE_PAYMENT' ? $bestCategory : $secondCategory;
                }
                if (str_contains($lowerText, 'balikin pinjaman')) {
                    return $bestKey === 'DEBT_PAYMENT' ? $bestCategory : $secondCategory;
                }
            }
        }

        return $matchedCategories[0]['category'];
    }

    /**
     * Resolve TransactionIntent from category model.
     */
    private function resolveIntent(Category $category): ?TransactionIntent
    {
        if ($category->system_key !== null) {
            return match ($category->system_key) {
                'TRANSFER' => TransactionIntent::Transfer,
                'LOAN' => TransactionIntent::Debt,
                'DEBT_PAYMENT' => TransactionIntent::Debt,
                'RECEIVABLE' => TransactionIntent::Receivable,
                'RECEIVABLE_PAYMENT' => TransactionIntent::Receivable,
                default => null,
            };
        }

        $typeName = $category->type->name ?? '';
        return TransactionIntent::tryFrom(strtolower($typeName)) ?? match ($typeName) {
            'Income' => TransactionIntent::Income,
            'Expense' => TransactionIntent::Expense,
            'Transfer' => TransactionIntent::Transfer,
            'Debt' => TransactionIntent::Debt,
            'Receivable' => TransactionIntent::Receivable,
            default => null,
        };
    }

    /**
     * Match Wallets using substring check.
     */
    private function matchWallets(string $text, $wallets, TransactionIntent $intent): array
    {
        $matchedWallets = [];
        $lowerText = mb_strtolower($text);

        foreach ($wallets as $wallet) {
            // Ignore system wallets for explicit matching
            if ($wallet->group_type === 'System') {
                continue;
            }

            $tokens = array_filter([
                $wallet->name,
                ...preg_split('/[,|;]+/', (string) $wallet->keyword, -1, PREG_SPLIT_NO_EMPTY)
            ]);

            foreach ($tokens as $token) {
                $token = trim(mb_strtolower($token));
                if ($token === '') continue;

                $pos = mb_strpos($lowerText, $token);
                if ($pos !== false) {
                    $matchedWallets[] = [
                        'wallet' => $wallet,
                        'token' => $token,
                        'offset' => $pos,
                    ];
                }
            }
        }

        // De-duplicate wallet matches (keep first occurrence)
        $uniqueMatches = [];
        foreach ($matchedWallets as $match) {
            $walletId = $match['wallet']->id;
            if (!isset($uniqueMatches[$walletId])) {
                $uniqueMatches[$walletId] = $match;
            }
        }
        $matchedWallets = array_values($uniqueMatches);

        // Sort by offset position in the text
        usort($matchedWallets, fn($a, $b) => $a['offset'] <=> $b['offset']);

        $sourceWallet = null;
        $destinationWallet = null;

        if ($intent === TransactionIntent::Transfer) {
            if (count($matchedWallets) >= 2) {
                $sourceWallet = $matchedWallets[0]['wallet']->name;
                $destinationWallet = $matchedWallets[1]['wallet']->name;
            } elseif (count($matchedWallets) === 1) {
                // If only one wallet is matched, check if it's preceded by "ke" or "to"
                $walletName = $matchedWallets[0]['wallet']->name;
                $offset = $matchedWallets[0]['offset'];
                $prefix = substr($text, max(0, $offset - 10), 10);
                if (preg_match('/\b(ke|to)\b/i', $prefix)) {
                    $destinationWallet = $walletName;
                } else {
                    $sourceWallet = $walletName;
                }
            }
        } else {
            // Single wallet matching
            if (count($matchedWallets) >= 1) {
                $sourceWallet = $matchedWallets[0]['wallet']->name;
            }
        }

        return [
            'sourceWallet' => $sourceWallet,
            'destinationWallet' => $destinationWallet,
        ];
    }

    /**
     * Extract Subject (Person's Name) for debt/receivable.
     */
    private function extractSubject(string $text, TransactionIntent $intent): ?string
    {
        // 1. Check hashtag
        if (preg_match('/#([a-zA-Z0-9_]+)/', $text, $matches)) {
            return $matches[1];
        }

        if ($intent !== TransactionIntent::Debt && $intent !== TransactionIntent::Receivable) {
            return null;
        }

        // 2. Pattern A: "Pinjamin Andi 100k" or "Kasih pinjam Budi"
        $patternA = '/(?:pinjamin|pinjamkan|ngutangin|kasih pinjam|pinjamkan ke|kasih pinjam ke|pinjam ke)\s+([A-Za-z]+)/i';
        if (preg_match($patternA, $text, $matches)) {
            return $matches[1];
        }

        // 3. Pattern B: "Iqbal bayar hutang" or "Budi balikin uang" or "Iqbal lunasin"
        $patternB = '/\b([A-Za-z]+)\s+(?:bayar|balikin|lunasin|mengembalikan|kembalikan|ngutang|utang|hutang|pinjam)\b/i';
        if (preg_match($patternB, $text, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
