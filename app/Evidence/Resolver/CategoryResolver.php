<?php

declare(strict_types=1);

namespace App\Evidence\Resolver;

use App\Models\Category;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * CategoryResolver — Rule-based category matching.
 *
 * Untuk transfer receipt, default category adalah Transfer.
 * Untuk shopping receipt, mencoba match berdasarkan merchant category.
 * Untuk document type lain, gunakan rules sederhana.
 */
class CategoryResolver
{
    /**
     * Resolve category berdasarkan transaction_type, document_type, dan merchant category.
     *
     * @return array{category_id: int|null, category_name: string|null, type_id: int|null, type_name: string|null, confidence: float}
     */
    public function resolve(
        User $user,
        ?string $transactionType,
        ?string $documentType,
        ?string $description,
        ?string $merchantCategory = null,
        ?string $merchantName = null,
    ): array {
        // Map transaction type ke type name
        $typeName = $this->mapTransactionType($transactionType, $documentType);

        if (! $typeName) {
            return [
                'category_id' => null,
                'category_name' => null,
                'type_id' => null,
                'type_name' => null,
                'confidence' => 0.0,
            ];
        }

        // Cari TransactionType
        $type = TransactionType::where('name', $typeName)->first();

        if (! $type) {
            Log::warning('CategoryResolver: transaction type not found', ['type_name' => $typeName]);

            return [
                'category_id' => null,
                'category_name' => null,
                'type_id' => null,
                'type_name' => $typeName,
                'confidence' => 0.3,
            ];
        }

        // Cari kategori yang cocok berdasarkan keyword
        $categories = Category::where('user_id', $user->id)
            ->where('type_id', $type->id)
            ->where('is_active', true)
            ->get();

        if ($categories->isEmpty()) {
            return [
                'category_id' => null,
                'category_name' => null,
                'type_id' => $type->id,
                'type_name' => $type->name,
                'confidence' => 0.4,
            ];
        }

        // 1a. Coba match berdasarkan QRIS merchant_categories config
        $qrisMatch = $this->matchByQrisMerchantCategory($merchantName ?? $description ?? '');
        if ($qrisMatch !== null) {
            $matchedCategory = $categories->first(function ($category) use ($qrisMatch) {
                return str_contains(strtolower($category->category_name), strtolower($qrisMatch['category_name']));
            });

            if ($matchedCategory) {
                return [
                    'category_id' => $matchedCategory->id,
                    'category_name' => $matchedCategory->category_name,
                    'type_id' => $type->id,
                    'type_name' => $type->name,
                    'confidence' => $qrisMatch['confidence'],
                ];
            }
        }

        // 1b. Coba match berdasarkan merchant category (untuk shopping receipt)
        if ($merchantCategory !== null) {
            $targetCategoryName = $this->mapMerchantCategoryToName($merchantCategory);

            if ($targetCategoryName !== null) {
                $matchedCategory = $categories->first(function ($category) use ($targetCategoryName) {
                    return str_contains(strtolower($category->category_name), strtolower($targetCategoryName));
                });

                if ($matchedCategory) {
                    Log::info('Category matched by merchant category', [
                        'category_id' => $matchedCategory->id,
                        'category_name' => $matchedCategory->category_name,
                        'merchant_category' => $merchantCategory,
                    ]);

                    return [
                        'category_id' => $matchedCategory->id,
                        'category_name' => $matchedCategory->category_name,
                        'type_id' => $type->id,
                        'type_name' => $type->name,
                        'confidence' => 0.85,
                    ];
                }
            }
        }

        // 2. Cari kategori berdasarkan keyword matching
        $descriptionLower = strtolower($description ?? '');
        $bestCategory = null;
        $bestScore = 0;

        foreach ($categories as $category) {
            if (! $category->keyword) {
                continue;
            }

            $keywords = array_map('trim', explode(',', strtolower($category->keyword)));
            foreach ($keywords as $keyword) {
                if (strlen($keyword) >= 2 && str_contains($descriptionLower, $keyword)) {
                    $score = strlen($keyword) / max(strlen($descriptionLower), 1);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestCategory = $category;
                    }
                }
            }
        }

        if ($bestCategory) {
            Log::info('Category matched by keyword', [
                'category_id' => $bestCategory->id,
                'category_name' => $bestCategory->category_name,
            ]);

            return [
                'category_id' => $bestCategory->id,
                'category_name' => $bestCategory->category_name,
                'type_id' => $type->id,
                'type_name' => $type->name,
                'confidence' => 0.75,
            ];
        }

        // Default: ambil kategori pertama
        $defaultCategory = $categories->first();

        return [
            'category_id' => $defaultCategory->id,
            'category_name' => $defaultCategory->category_name,
            'type_id' => $type->id,
            'type_name' => $type->name,
            'confidence' => 0.5,
        ];
    }

    /**
     * Map merchant category ke nama kategori yang diharapkan.
     */
    private function mapMerchantCategoryToName(string $merchantCategory): ?string
    {
        $qrisCategories = config('qris_parser.merchant_categories', []);

        // Cek di qris_parser merchant_categories
        foreach ($qrisCategories as $categoryName => $keywords) {
            $key = strtolower(str_replace(' & ', '_', $categoryName));
            if ($merchantCategory === $key) {
                return $categoryName;
            }
        }

        return match ($merchantCategory) {
            'restaurant' => 'Makan & Minum',
            'retail' => 'Belanja',
            'pharmacy' => 'Kesehatan',
            default => null,
        };
    }

    /**
     * Cari kategori berdasarkan merchant name menggunakan QRIS merchant_categories config.
     */
    private function matchByQrisMerchantCategory(string $merchantName): ?array
    {
        $qrisCategories = config('qris_parser.merchant_categories', []);
        $lower = strtolower($merchantName);

        foreach ($qrisCategories as $categoryName => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $targetName = $this->mapMerchantCategoryToName(strtolower(str_replace(' & ', '_', $categoryName)));
                    if ($targetName !== null) {
                        return ['category_name' => $targetName, 'confidence' => 0.8];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Map transaction_type + document_type ke type name.
     */
    private function mapTransactionType(?string $transactionType, ?string $documentType): ?string
    {
        // Transfer receipt → Transfer
        if ($documentType === 'TRANSFER_RECEIPT' || strtolower($transactionType ?? '') === 'transfer') {
            return 'Transfer';
        }

        // Payment receipt → Expense
        if ($documentType === 'PAYMENT_RECEIPT') {
            return 'Expense';
        }

        // Topup receipt → Income
        if ($documentType === 'TOPUP_RECEIPT') {
            return 'Income';
        }

        // Withdraw receipt → Expense
        if ($documentType === 'WITHDRAW_RECEIPT') {
            return 'Expense';
        }

        // Deposit receipt → Income
        if ($documentType === 'DEPOSIT_RECEIPT') {
            return 'Income';
        }

        // Shopping receipt → Expense
        if ($documentType === 'SHOPPING_RECEIPT') {
            return 'Expense';
        }

        // QRIS receipt → Expense
        if ($documentType === 'QRIS_RECEIPT') {
            return 'Expense';
        }

        // Fallback based on transaction type
        return match (strtolower($transactionType ?? '')) {
            'income' => 'Income',
            'expense' => 'Expense',
            'transfer' => 'Transfer',
            default => 'Expense',
        };
    }
}
