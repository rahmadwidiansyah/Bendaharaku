<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserAiMemory;
use App\Services\AI\Memory\MemoryDecayEngine;

class PruneAiMemoriesCommand extends Command
{
    protected $signature = 'ai:prune-memories';
    protected $description = 'Mengkalkulasi decay dan menghapus memori kognitif AI yang sudah usang.';

public function handle(\App\Services\AI\Memory\MemoryDecayEngine $decayEngine): int
    {
        $threshold = (float) config('bendaharaku.ai.memory.prune_threshold', 0.20);
        $prunedCount = 0;

        // Gunakan lazy collection (cursor) agar RAM aman berapapun jumlah datanya
        foreach (\App\Models\UserAiMemory::cursor() as $memory) {
            $newWeight = $decayEngine->calculateDecayedWeight(
                (float) $memory->weight, 
                $memory->last_applied_at ?? $memory->created_at
            );

            if ($newWeight < $threshold) {
                $memory->delete();
                $prunedCount++;
            } else {
                $memory->update(['weight' => $newWeight]);
            }
        }

        $this->info("Berhasil melakukan pruning pada {$prunedCount} memori usang.");
        return self::SUCCESS;
    }
};