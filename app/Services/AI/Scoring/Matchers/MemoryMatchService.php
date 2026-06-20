<?php

declare(strict_types=1);

namespace App\Services\AI\Scoring\Matchers;

class MemoryMatchService
{
    public function calculateScore(string $inputText, array $activeMemories): float
    {
        if (empty($activeMemories)) return 0.0;

        $inputLower = strtolower($inputText);
        $highestWeight = 0.0;

        foreach ($activeMemories as $memory) {
            if (str_contains($inputLower, strtolower($memory['keyword']))) {
                $highestWeight = max($highestWeight, (float) $memory['effective_weight']);
            }
        }

        $maxEffective = (float) config('bendaharaku.ai.memory.max_effective_weight', 10.0);
        
        return min(1.0, $highestWeight / $maxEffective);
    }
}