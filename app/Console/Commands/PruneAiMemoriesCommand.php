<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\UserAiMemory;
use App\Models\UserAiMemoryLog;
use App\Services\AI\Memory\MemoryDecayEngine;
use Illuminate\Console\Command;

class PruneAiMemoriesCommand extends Command
{
    protected $signature = 'ai:prune-memories';

    protected $description = 'Mengkalkulasi decay dan menghapus memori kognitif AI yang sudah usang.';

    public function handle(MemoryDecayEngine $decayEngine): int
    {
        $threshold = (float) config('bendaharaku.ai.memory.prune_threshold', 0.20);
        $protectMinHits = (int) config('bendaharaku.ai.memory.protect_min_hits', 3);
        $protectMinWeight = (float) config('bendaharaku.ai.memory.protect_min_weight', 3.0);
        $protectFloor = (float) config('bendaharaku.ai.memory.protect_floor', 1.0);
        $prunedCount = 0;
        $decayedCount = 0;
        $protectedCount = 0;

        foreach (UserAiMemory::cursor() as $memory) {
            $oldWeight = (float) $memory->weight;

            $newWeight = $decayEngine->calculateDecayedWeight(
                $oldWeight,
                $memory->last_applied_at ?? $memory->created_at
            );

            // Memory terbukti (sering dipakai / bobot tinggi) tidak boleh hilang karena decay.
            $isProven = (int) $memory->hit_count >= $protectMinHits || $oldWeight >= $protectMinWeight;

            if ($isProven) {
                $protectedWeight = max($newWeight, $protectFloor);

                UserAiMemoryLog::create([
                    'memory_id' => $memory->id,
                    'user_id' => $memory->user_id,
                    'action' => 'PROTECTED',
                    'raw_subject' => $memory->raw_subject,
                    'normalized_subject' => $memory->normalized_subject,
                    'memory_keyword' => $memory->memory_keyword,
                    'old_weight' => $oldWeight,
                    'new_weight' => $protectedWeight,
                    'old_hit_count' => $memory->hit_count,
                    'new_hit_count' => $memory->hit_count,
                    'reason' => sprintf('Proven memory (hits %d, weight %.2f)', $memory->hit_count, $oldWeight),
                    'metadata' => [
                        'protect_min_hits' => $protectMinHits,
                        'protect_min_weight' => $protectMinWeight,
                        'protect_floor' => $protectFloor,
                    ],
                    'algorithm_version' => 'v1-keyword',
                ]);

                $memory->update(['weight' => $protectedWeight]);
                $protectedCount++;

                continue;
            }

            if ($newWeight < $threshold) {
                UserAiMemoryLog::create([
                    'memory_id' => $memory->id,
                    'user_id' => $memory->user_id,
                    'action' => 'PRUNED',
                    'raw_subject' => $memory->raw_subject,
                    'normalized_subject' => $memory->normalized_subject,
                    'memory_keyword' => $memory->memory_keyword,
                    'old_weight' => $oldWeight,
                    'new_weight' => 0.0,
                    'old_hit_count' => $memory->hit_count,
                    'new_hit_count' => $memory->hit_count,
                    'reason' => 'Below threshold ('.number_format($threshold, 2).')',
                    'metadata' => ['threshold' => $threshold],
                    'algorithm_version' => 'v1-keyword',
                ]);

                $memory->delete();
                $prunedCount++;
            } else {
                $daysElapsed = now()->diffInDays($memory->last_applied_at ?? $memory->created_at);

                UserAiMemoryLog::create([
                    'memory_id' => $memory->id,
                    'user_id' => $memory->user_id,
                    'action' => 'DECAYED',
                    'raw_subject' => $memory->raw_subject,
                    'normalized_subject' => $memory->normalized_subject,
                    'memory_keyword' => $memory->memory_keyword,
                    'old_weight' => $oldWeight,
                    'new_weight' => $newWeight,
                    'old_hit_count' => $memory->hit_count,
                    'new_hit_count' => $memory->hit_count,
                    'reason' => "Daily decay over {$daysElapsed} days",
                    'metadata' => ['days_elapsed' => $daysElapsed],
                    'algorithm_version' => 'v1-keyword',
                ]);

                $memory->update(['weight' => $newWeight]);
                $decayedCount++;
            }
        }

        $this->info("Pruned {$prunedCount} memories, decayed {$decayedCount} memories, protected {$protectedCount} memories.");

        return self::SUCCESS;
    }
}
