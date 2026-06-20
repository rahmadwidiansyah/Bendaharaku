<?php

declare(strict_types=1);

namespace App\Services\AI\Scoring\Matchers;

use App\Models\User;

class CategoryMatchService
{
    public function isMatch(User $user, ?string $categoryText): bool
    {
        if (blank($categoryText)) return false;

        $search = strtolower(trim($categoryText));
        $categories = $user->categories()->get(['category_name', 'keyword']);

        return $categories->contains(function ($c) use ($search) {
            if (strtolower($c->category_name) === $search) return true;
            
            if (blank($c->keyword)) return false;
            $tokens = preg_split('/[,|;]+/', strtolower($c->keyword), -1, PREG_SPLIT_NO_EMPTY);
            return in_array($search, array_map('trim', $tokens), true);
        });
    }
}