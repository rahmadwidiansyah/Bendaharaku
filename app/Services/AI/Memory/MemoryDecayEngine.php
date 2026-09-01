<?php

declare(strict_types=1);

namespace App\Services\AI\Memory;

use DateTimeInterface;
use Illuminate\Support\Carbon;

class MemoryDecayEngine
{
    /**
     * Menghitung bobot baru berdasarkan waktu yang berlalu (Exponential Decay).
     * Rumus: W = W0 * e^(-λ * t)
     */
    public function calculateDecayedWeight(float $currentWeight, DateTimeInterface $lastAppliedAt): float
    {
        $lastApplied = Carbon::instance($lastAppliedAt);
        $daysElapsed = (int) $lastApplied->diffInDays(now());

        if ($daysElapsed <= 0) {
            return $currentWeight;
        }

        $lambda = (float) config('bendaharaku.ai.memory.decay_rate', 0.05);

        // $currentWeight * exp(-lambda * days)
        $decayedWeight = $currentWeight * exp(-$lambda * $daysElapsed);

        return round($decayedWeight, 4);
    }
}
