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
        $prunedCount = 0;
        $decayedCount = 0;

        foreach (UserAiMemory::cursor() as $memory) {
            $oldWeight = (float) $memory->weight;

            $newWeight = $decayEngine->calculateDecayedWeight(
                $oldWeight,
                $memory->last_applied_at ?? $memory->created_at
            );

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

        $this->info("Pruned {$prunedCount} memories, decayed {$decayedCount} memories.");

        return self::SUCCESS;
    }
}
