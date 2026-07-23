<?php

declare(strict_types=1);

namespace App\Services\AI\Context;

class AIContextBuilder
{
    private const MAX_WALLETS = 10;
    private const MAX_CATEGORIES = 20;

    public function build(ContextSnapshot $snapshot): AIContext
    {
        return new AIContext(
            userInput: $snapshot->userInput,
            conversationId: null,
            wallets: $this->buildWallets($snapshot),
            categories: $this->buildCategories($snapshot),
            keywordAliases: $this->buildKeywordAliases($snapshot),
            activeMemories: $snapshot->activeMemories,
            today: now()->toDateString(),
            timezone: $snapshot->user->timezone ?? 'Asia/Jakarta',
            locale: $snapshot->user->locale ?? 'id',
        );
    }

    private function buildWallets(ContextSnapshot $snapshot): array
    {
        $wallets = $snapshot->wallets
            ->where('group_type', '!=', 'System')
            ->sortByDesc('is_pinned')
            ->sortBy('name')
            ->take(self::MAX_WALLETS)
            ->values()
            ->toArray();

        return array_map(fn (array $w) => [
            'id' => $w['id'],
            'name' => $w['name'],
        ], $wallets);
    }

    private function buildCategories(ContextSnapshot $snapshot): array
    {
        $categories = $snapshot->categories
            ->take(self::MAX_CATEGORIES)
            ->values()
            ->toArray();

        return array_map(fn (array $c) => [
            'id' => $c['id'],
            'name' => $c['category_name'],
        ], $categories);
    }

    private function buildKeywordAliases(ContextSnapshot $snapshot): array
    {
        $aliases = [];

        foreach ($snapshot->wallets as $wallet) {
            $name = $wallet->name;
            if (blank($name)) {
                continue;
            }
            $aliases[mb_strtolower(trim($name))] = $name;
            foreach ($this->splitKeywords($wallet->keyword ?? '') as $kw) {
                $aliases[mb_strtolower(trim($kw))] = $name;
            }
        }

        foreach ($snapshot->categories as $category) {
            $name = $category->category_name;
            if (blank($name)) {
                continue;
            }
            $aliases[mb_strtolower(trim($name))] = $name;
            foreach ($this->splitKeywords($category->keyword ?? '') as $kw) {
                $aliases[mb_strtolower(trim($kw))] = $name;
            }
        }

        return $aliases;
    }

    private function splitKeywords(string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/[,|;]+/', mb_strtolower($raw)))
        ));
    }
}
