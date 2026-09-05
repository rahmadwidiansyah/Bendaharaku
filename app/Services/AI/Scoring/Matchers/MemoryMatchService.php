<?php

declare(strict_types=1);

namespace App\Services\AI\Scoring\Matchers;

class MemoryMatchService
{
    public function calculateScore(string $inputText, array $activeMemories): float
    {
        if (empty($activeMemories)) {
            return 0.0;
        }

        $inputLower = mb_strtolower($inputText);
        $highestWeight = 0.0;

        foreach ($activeMemories as $memory) {
            $keyword = $memory['keyword'] ?? '';
            if (! $this->memoryContains($inputLower, $keyword)) {
                continue;
            }

            $highestWeight = max($highestWeight, (float) $memory['effective_weight']);
        }

        $maxEffective = (float) config('bendaharaku.ai.memory.max_effective_weight', 10.0);

        return min(1.0, $highestWeight / $maxEffective);
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
}
