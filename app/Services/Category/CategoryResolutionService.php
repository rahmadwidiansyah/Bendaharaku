<?php

declare(strict_types=1);

namespace App\Services\Category;

use App\Exceptions\CategoryNotFoundException;
use App\Models\Category;
use App\Models\TransactionType;
use App\Models\User;
use App\Support\StringUtils;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class CategoryResolutionService
{
    public function resolveByName(string $name, Collection $categories, bool $throwOnNotFound = true): ?Category
    {
        $match = StringUtils::findByNameOrKeyword($categories, $name, 'category_name');
        if ($match !== null) {
            return $match;
        }

        if ($throwOnNotFound) {
            throw new CategoryNotFoundException("Kategori '{$name}' tidak terdaftar.");
        }

        return null;
    }

    public function resolveFromText(string $text, Collection $categories, ?string $subject = null): ?Category
    {
        $lowerText = StringUtils::normalize($text);
        $matchedCategories = [];

        foreach ($categories as $category) {
            $nameToken = StringUtils::normalize($category->category_name);
            $keywordTokens = StringUtils::tokenizeKeywords($category->keyword);
            $tokens = array_values(array_filter([$nameToken, ...$keywordTokens]));

            foreach ($tokens as $token) {
                if ($token === '') {
                    continue;
                }

                if (in_array($token, ['utang', 'hutang', 'ngutang']) && str_contains($lowerText, 'piutang')) {
                    $utangCount = substr_count($lowerText, $token);
                    $piutangCount = substr_count($lowerText, 'piutang');
                    if ($utangCount <= $piutangCount) {
                        continue;
                    }
                }

                if (str_contains($lowerText, $token)) {
                    $matchedCategories[] = [
                        'category' => $category,
                        'token' => $token,
                        'length' => strlen($token),
                    ];
                }
            }
        }

        if (empty($matchedCategories)) {
            return null;
        }

        usort($matchedCategories, fn ($a, $b) => $b['length'] <=> $a['length']);

        if (count($matchedCategories) >= 2 && $matchedCategories[0]['length'] === $matchedCategories[1]['length']) {
            $bestCategory = $matchedCategories[0]['category'];
            $secondCategory = $matchedCategories[1]['category'];
            $bestKey = $bestCategory->system_key;
            $secondKey = $secondCategory->system_key;

            if ($bestKey === null && $secondKey !== null) {
                return $secondCategory;
            }
            if ($secondKey === null && $bestKey !== null) {
                return $bestCategory;
            }
        }

        return $matchedCategories[0]['category'];
    }

    public function resolveSystemCategory(int $userId, string $type, ?string $subType = null, ?int $categoryId = null): Category
    {
        if (! empty($categoryId)) {
            $cat = Category::where('user_id', $userId)->where('id', $categoryId)->first();
            if ($cat) {
                return $cat;
            }
        }

        $systemKey = match (true) {
            $type === 'debt' && in_array($subType, ['loan', 'income', null]) => 'LOAN',
            $type === 'debt' && in_array($subType, ['payment', 'expense']) => 'DEBT_PAYMENT',
            $type === 'receivable' && in_array($subType, ['give', 'expense']) => 'RECEIVABLE',
            $type === 'receivable' && in_array($subType, ['receive', 'income']) => 'RECEIVABLE_PAYMENT',
            $type === 'debt' => 'LOAN',
            $type === 'receivable' => 'RECEIVABLE',
            default => null,
        };

        if ($systemKey !== null) {
            $cat = Category::where('user_id', $userId)->where('system_key', $systemKey)->first();
            if ($cat) {
                return $cat;
            }
        }

        $transactionType = TransactionType::where('name', ucfirst($type))->first();
        if ($transactionType) {
            $cat = Category::where('user_id', $userId)->where('type_id', $transactionType->id)->first();
            if ($cat) {
                return $cat;
            }
        }

        throw new CategoryNotFoundException("Kategori sistem untuk tipe '{$type}' tidak ditemukan.");
    }

    public function resolveTransferCategory(int $userId): Category
    {
        $category = Category::where('user_id', $userId)
            ->where('system_key', 'TRANSFER')
            ->first();

        if (! $category) {
            $transferType = TransactionType::where('name', 'Transfer')->first();
            if ($transferType) {
                $category = Category::where('user_id', $userId)
                    ->where('type_id', $transferType->id)
                    ->first();
            }
        }

        if (! $category) {
            $transferType = TransactionType::firstOrCreate(
                ['name' => 'Transfer'],
                ['keyword' => 'trf']
            );

            $category = Category::create([
                'user_id' => $userId,
                'type_id' => $transferType->id,
                'category_name' => 'Transfer Saldo',
                'icon' => '🔄',
                'keyword' => 'trf, transfer',
                'is_active' => true,
                'system_key' => 'TRANSFER',
            ]);
        }

        return $category;
    }

    public function isCategoryRequired(string $type): bool
    {
        return ! in_array(strtolower($type), ['transfer', 'debt', 'receivable']);
    }

    public function buildPromptContext(Collection $categories): array
    {
        $names = [];
        $keywordMap = [];

        foreach ($categories as $c) {
            $names[] = $c['category_name'] ?? $c->category_name;
            foreach (StringUtils::splitKeywords($c['keyword'] ?? $c->keyword ?? '') as $kw) {
                if (! empty($kw)) {
                    $keywordMap[strtolower(trim($kw))] = $c['category_name'] ?? $c->category_name;
                }
            }
        }

        return [
            'category_names' => array_values(array_filter($names)),
            'keyword_aliases' => $keywordMap,
        ];
    }
}
