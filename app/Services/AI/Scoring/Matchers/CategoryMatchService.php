<?php

declare(strict_types=1);

namespace App\Services\AI\Scoring\Matchers;

use App\Models\User;
use App\Support\StringUtils;

class CategoryMatchService
{
    public function isMatch(User $user, ?string $categoryText): bool
    {
        if (blank($categoryText)) {
            return false;
        }

        $categories = $user->categories()->get(['category_name', 'keyword']);

        return StringUtils::findByNameOrKeyword($categories, $categoryText) !== null;
    }
}
