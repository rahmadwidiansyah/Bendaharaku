<?php

declare(strict_types=1);

namespace App\Services\AI\Scoring\Matchers;

use App\Support\StringUtils;
use Illuminate\Database\Eloquent\Collection;

class CategoryMatchService
{
    public function isMatch(Collection $categories, ?string $categoryText): bool
    {
        if (blank($categoryText)) {
            return false;
        }

        return StringUtils::findByNameOrKeyword($categories, $categoryText) !== null;
    }
}
