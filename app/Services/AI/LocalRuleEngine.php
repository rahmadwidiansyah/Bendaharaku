<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AIParseResult;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Category\CategoryResolutionService;
use App\Services\Wallet\WalletResolutionService;
use Illuminate\Support\Facades\Log;

class LocalRuleEngine
{
    public function __construct(
        private readonly CategoryResolutionService $categoryResolution,
        private readonly WalletResolutionService $walletResolution,
    ) {}

    /**
     * Try to parse transaction using regex and keyword rules.
     * Returns AIParseResult on success, or null on failure.
     */
    public function parse(User $user, string $text): ?AIParseResult
    {
        $normalizedText = trim($text);
        $traceId = uniqid('LRE-'); // Temporary trace ID

        Log::debug('LocalRuleEngine: [TRACE_ID: {trace_id}] Start parsing', [
            'trace_id' => $traceId,
            'user_id' => $user->id,
            'text' => $text,
        ]);

        if ($normalizedText === '') {
            Log::debug('LocalRuleEngine: [TRACE_ID: {trace_id}] Empty text, returning null', [
                'trace_id' => $traceId,
                'user_id' => $user->id,
            ]);

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

        // 3. Provisional Subject Extraction for Scoring
        $provisionalSubject = $this->extractSubjectSimple($normalizedText);

        // 4. Match Category with Indonesian NLP scoring
        $categoryMatch = $this->matchCategory($normalizedText, $categories, $provisionalSubject);
        if ($categoryMatch === null) {
            return null; // Cannot determine category
        }

        // 5. Resolve Transaction Intent
        $intent = $this->resolveIntent($categoryMatch);
        if ($intent === null) {
            return null;
        }

        // 6. Match Wallets
        $walletData = $this->matchWallets($normalizedText, $wallets, $intent);

        // 7. Extract Subject for Debt/Receivable
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

        $isCleared = $hasRequiredWallets && ($subject !== null || ! in_array($intent, [TransactionIntent::Debt, TransactionIntent::Receivable]));

        // Construct ParsedTransaction
        $parsedTransaction = new ParsedTransaction(
            amount: $amount,
            transactionType: $intent,
            category: $categoryMatch->category_name,
            sourceWallet: $walletData['sourceWallet'],
            destinationWallet: $walletData['destinationWallet'],
            subject: $subject,
            notes: $text,
            isCleared: $isCleared,
            useAllBalance: $useAllBalance
        );

        $parseResult = new AIParseResult(
            success: true,
            confidence: 1.0, // Rule engine matches are 100% confident
            error: null,
            transaction: $parsedTransaction,
            usage: ['prompt' => 0, 'completion' => 0, 'total' => 0],
            provider: 'local-rules',
            model: 'regex'
        );

        Log::debug('LocalRuleEngine: [TRACE_ID: {trace_id}] Successfully parsed locally', [
            'trace_id' => $traceId,
            'user_id' => $user->id,
            'text' => $text,
            'intent' => $intent->value,
            'category' => $categoryMatch->category_name,
            'amount' => $amount,
            'source_wallet' => $walletData['sourceWallet'],
            'destination_wallet' => $walletData['destinationWallet'],
            'subject' => $subject,
            'is_cleared' => $isCleared,
            'use_all_balance' => $useAllBalance,
        ]);

        return $parseResult;
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
                        'useAllBalance' => $useAllBalance,
                    ];
                }
            }
        }

        if ($useAllBalance) {
            return [
                'amount' => 0.0,
                'useAllBalance' => true,
            ];
        }

        return null;
    }

    /**
     * Match Category using longest token match logic with substring support and NLP scoring.
     */
    private function matchCategory(string $text, $categories, ?string $subject): ?Category
    {
        $lowerText = mb_strtolower($text);

        // 1. Run scoring engine for the 4 system categories (LOAN, DEBT_PAYMENT, RECEIVABLE, RECEIVABLE_PAYMENT)
        $scoredSystemKey = $this->scoreSystemCategory($lowerText, $subject);
        if ($scoredSystemKey !== null) {
            $systemCategory = $categories->firstWhere('system_key', $scoredSystemKey);
            if ($systemCategory) {
                return $systemCategory;
            }
        }

        // 2. Fallback to token matching via CategoryResolutionService
        return $this->categoryResolution->resolveFromText($text, $categories, $subject);
    }

    /**
     * Indonesian NLP scoring logic for Hutang / Piutang system categories.
     */
    private function scoreSystemCategory(string $text, ?string $subject): ?string
    {
        $scores = [
            'LOAN' => 0,
            'DEBT_PAYMENT' => 0,
            'RECEIVABLE' => 0,
            'RECEIVABLE_PAYMENT' => 0,
        ];

        $hasBayar = str_contains($text, 'bayar') || str_contains($text, 'lunas') || str_contains($text, 'nyicil') || str_contains($text, 'cicil');
        $hasBalikin = str_contains($text, 'balikin') || str_contains($text, 'kembali') || str_contains($text, 'ganti');
        $hasPayment = $hasBayar || $hasBalikin;

        $hasHutang = str_contains($text, 'hutang') || str_contains($text, 'utang');
        // Prevent 'utang' matching inside 'piutang'
        if ($hasHutang && str_contains($text, 'piutang')) {
            $utangCount = substr_count($text, 'utang') + substr_count($text, 'hutang');
            $piutangCount = substr_count($text, 'piutang');
            if ($utangCount <= $piutangCount) {
                $hasHutang = false;
            }
        }

        $hasPiutang = str_contains($text, 'piutang');

        $hasPinjam = str_contains($text, 'pinjam') || str_contains($text, 'pinjem') || str_contains($text, 'minjam') || str_contains($text, 'minjem');
        $hasPinjamin = str_contains($text, 'pinjamin') || str_contains($text, 'pinjemin') || str_contains($text, 'pinjamkan') || str_contains($text, 'ngutangin') || str_contains($text, 'ngasih pinjam') || str_contains($text, 'kasih pinjam') || str_contains($text, 'kasih utang') || str_contains($text, 'meminjamkan');

        $hasKe = (bool) preg_match('/\b(ke|kepada)\b/u', $text);

        // Rule 1: pinjamin/ngutangin/kasih pinjam -> RECEIVABLE
        if ($hasPinjamin) {
            $scores['RECEIVABLE'] += 100;
            $scores['LOAN'] -= 50;
        }

        // Rule 2: bayar/balikin piutang -> RECEIVABLE_PAYMENT
        if ($hasPayment && $hasPiutang) {
            $scores['RECEIVABLE_PAYMENT'] += 100;
            $scores['DEBT_PAYMENT'] -= 50;
        }

        // Rule 3: bayar/balikin/lunasi hutang/pinjaman
        if ($hasPayment && ($hasHutang || $hasPinjam) && ! $hasPinjamin) {
            if ($hasKe) {
                // "bayar hutang ke budi" -> DEBT_PAYMENT
                $scores['DEBT_PAYMENT'] += 100;
                $scores['RECEIVABLE_PAYMENT'] -= 50;
            } elseif ($subject !== null && $subject !== '-') {
                // "budi bayar hutang" / "budi balikin pinjaman" -> RECEIVABLE_PAYMENT
                $scores['RECEIVABLE_PAYMENT'] += 100;
                $scores['DEBT_PAYMENT'] -= 50;
            } else {
                // Ambiguous, default to paying our own debt
                $scores['DEBT_PAYMENT'] += 70;
            }
        }

        // Rule 4: "balikin uang" / "mengembalikan uang" / "balikin duit"
        if ($hasBalikin && (str_contains($text, 'uang') || str_contains($text, 'duit') || str_contains($text, 'pinjaman'))) {
            if ($hasKe) {
                $scores['DEBT_PAYMENT'] += 100;
                $scores['RECEIVABLE_PAYMENT'] -= 50;
            } elseif ($subject !== null && $subject !== '-') {
                $scores['RECEIVABLE_PAYMENT'] += 100;
                $scores['DEBT_PAYMENT'] -= 50;
            } else {
                $scores['DEBT_PAYMENT'] += 70;
            }
        }

        // Rule 5: general pinjam/hutang without payment keywords
        if (! $hasPayment && ($hasHutang || $hasPinjam) && ! $hasPinjamin) {
            if ($hasKe) {
                // "hutang ke budi", "pinjam ke budi" -> LOAN
                $scores['LOAN'] += 100;
                $scores['RECEIVABLE'] -= 50;
            } elseif ($subject !== null && $subject !== '-') {
                // "budi ngutang", "budi pinjam" -> RECEIVABLE
                $scores['RECEIVABLE'] += 80;
                $scores['LOAN'] -= 40;
            } else {
                // Default to LOAN (dapat hutangan)
                $scores['LOAN'] += 70;
            }
        }

        // Rule 6: Ngasih piutang (general receivable without payment)
        if (! $hasPayment && $hasPiutang) {
            $scores['RECEIVABLE'] += 90;
        }

        arsort($scores);
        $maxScore = reset($scores);
        $bestKey = key($scores);

        if ($maxScore > 0) {
            return $bestKey;
        }

        return null;
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

    private function matchWallets(string $text, $wallets, TransactionIntent $intent): array
    {
        return $this->walletResolution->matchWalletsFromText($text, $wallets, $intent);
    }

    /**
     * Provisional subject extraction without intent dependencies.
     */
    private function extractSubjectSimple(string $text): ?string
    {
        // 1. Check hashtag
        if (preg_match('/#([a-zA-Z0-9_]+)/', $text, $matches)) {
            return $matches[1];
        }

        // 2. Pattern A: "Pinjamin Andi 100k"
        $patternA = '/(?:pinjamin|pinjamkan|ngutangin|kasih pinjam|pinjamkan ke|kasih pinjam ke|pinjam ke)\s+([A-Za-z]+)/i';
        if (preg_match($patternA, $text, $matches)) {
            return $matches[1];
        }

        // 3. Pattern B: "Iqbal bayar hutang"
        $patternB = '/\b([A-Za-z]+)\s+(?:bayar|balikin|lunasin|mengembalikan|kembalikan|ngutang|utang|hutang|pinjam)\b/i';
        if (preg_match($patternB, $text, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract Subject (Person's Name) for debt/receivable.
     */
    private function extractSubject(string $text, TransactionIntent $intent): ?string
    {
        if ($intent !== TransactionIntent::Debt && $intent !== TransactionIntent::Receivable) {
            return null;
        }

        return $this->extractSubjectSimple($text);
    }
}
