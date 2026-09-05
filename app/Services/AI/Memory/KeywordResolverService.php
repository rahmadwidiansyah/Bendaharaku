<?php

declare(strict_types=1);

namespace App\Services\AI\Memory;

use App\DTO\KeywordResolverResult;
use App\Models\UserAiMemory;
use App\Support\StringUtils;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class KeywordResolverService
{
    public function __construct(
        private readonly MemoryDecayEngine $decayEngine,
    ) {}

    public function resolveCategory(
        ?string $text,
        Collection $categories,
        int $userId,
    ): KeywordResolverResult {
        if (blank($text)) {
            return KeywordResolverResult::notFound();
        }

        $builtIn = StringUtils::findByNameOrKeyword($categories, $text, 'category_name');
        if ($builtIn !== null) {
            return new KeywordResolverResult(
                targetId: $builtIn->id,
                targetName: $builtIn->category_name,
                matchedBy: 'builtin_keyword',
                matchedKeyword: $text,
            );
        }

        return $this->resolveFromMemory($text, $userId, 'category');
    }

    public function resolveWallet(
        ?string $text,
        Collection $wallets,
        int $userId,
    ): KeywordResolverResult {
        if (blank($text)) {
            return KeywordResolverResult::notFound();
        }

        $builtIn = StringUtils::findByNameOrKeyword($wallets, $text);
        if ($builtIn !== null) {
            return new KeywordResolverResult(
                targetId: $builtIn->id,
                targetName: $builtIn->name,
                matchedBy: 'builtin_keyword',
                matchedKeyword: $text,
            );
        }

        return $this->resolveFromMemory($text, $userId, 'wallet');
    }

    private function resolveFromMemory(string $text, int $userId, string $targetType): KeywordResolverResult
    {
        $memories = $this->getUserMemories($userId);
        $textLower = mb_strtolower($text);
        $bestResult = null;
        $bestWeight = -1.0;

        foreach ($memories as $memory) {
            if (! ($memory instanceof UserAiMemory)) {
                continue;
            }

            $pattern = $memory->keyword_pattern;
            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            if ($memory->target_type !== null && $memory->target_type !== $targetType) {
                continue;
            }

            if (! $this->memoryContains($textLower, $pattern)) {
                continue;
            }

            $decayedWeight = $this->decayEngine->calculateDecayedWeight(
                (float) $memory->weight,
                $memory->last_applied_at ?? $memory->created_at
            );

            if ($decayedWeight < 0.1) {
                continue;
            }

            if ($decayedWeight <= $bestWeight) {
                continue;
            }

            if ($targetType === 'category' && $memory->category_id !== null) {
                $bestResult = new KeywordResolverResult(
                    targetId: $memory->category_id,
                    targetName: $memory->category?->category_name,
                    matchedBy: 'user_memory',
                    matchedKeyword: $pattern,
                );
                $bestWeight = $decayedWeight;
            } elseif ($targetType === 'wallet' && $memory->wallet_id !== null) {
                $bestResult = new KeywordResolverResult(
                    targetId: $memory->wallet_id,
                    targetName: $memory->wallet?->name,
                    matchedBy: 'user_memory',
                    matchedKeyword: $pattern,
                );
                $bestWeight = $decayedWeight;
            }
        }

        return $bestResult ?? KeywordResolverResult::notFound();
    }

    private function memoryContains(string $textLower, string $keyword): bool
    {
        $k = trim(mb_strtolower($keyword));
        if ($k === '' || mb_strlen($k) < 3) {
            return false;
        }
        $escaped = preg_quote($k, '/');
        return (bool) preg_match('/(?<![\p{L}\p{N}_])'.$escaped.'(?![\p{L}\p{N}_])/iu', $textLower);
    }

    private function getUserMemories(int $userId): \Illuminate\Support\Collection
    {
        $cacheKey = "ai-mem-resolve-{$userId}";

        $memories = Cache::remember($cacheKey, 300, function () use ($userId) {
            return UserAiMemory::where('user_id', $userId)
                ->with(['category:id,category_name', 'wallet:id,name'])
                ->orderByDesc('weight')
                ->get();
        });

        if (! ($memories instanceof \Illuminate\Support\Collection)) {
            Cache::forget($cacheKey);

            return UserAiMemory::where('user_id', $userId)
                ->with(['category:id,category_name', 'wallet:id,name'])
                ->orderByDesc('weight')
                ->get();
        }

        return $memories;
    }
}
