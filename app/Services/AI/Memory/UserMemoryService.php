<?php

declare(strict_types=1);

namespace App\Services\AI\Memory;

use App\Models\UserAiMemory;
use App\Models\UserAiMemoryContribution;
use App\Models\UserAiMemoryLog;
use App\Support\ActivityLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

readonly class UserMemoryService
{
    public function __construct(
        private MemoryDecayEngine $decayEngine,
        private MemoryKeywordExtractor $extractor,
    ) {}

    /**
     * Membangun memori hanya dari entitas Subject (Pihak/Merchant) yang diverifikasi.
     * Subject akan dinormalisasi oleh MemoryKeywordExtractor sebelum disimpan.
     */
    public function upsertMemory(int $userId, array $correctedData, ?string $source = null, ?int $transactionId = null): void
    {
        $subject = $correctedData['subject'] ?? null;

        if (blank($subject) || $subject === '-' || $subject === 'System') {
            return;
        }

        $extracted = $this->extractor->extract($subject);
        $keyword = $extracted['keyword'];

        if (strlen($keyword) < 3) {
            return;
        }

        $targetType = ! empty($correctedData['category_id']) ? 'category' : 'wallet';

        DB::transaction(function () use ($userId, $keyword, $extracted, $correctedData, $source, $transactionId, $targetType) {
            $memory = UserAiMemory::firstOrNew([
                'user_id' => $userId,
                'keyword_pattern' => $keyword,
                'target_type' => $targetType,
            ]);

            $isNew = ! $memory->exists;
            $oldWeight = (float) ($memory->weight ?? 0.0);
            $oldHitCount = (int) ($memory->hit_count ?? 0);

            $decayedWeight = $memory->exists
                ? $this->decayEngine->calculateDecayedWeight($oldWeight, $memory->last_applied_at ?? now())
                : 0.0;

            $newWeight = min(5.0, $decayedWeight + 1.0);
            $newHitCount = $oldHitCount + 1;

            $memory->fill([
                'raw_subject' => $extracted['raw'],
                'normalized_subject' => $extracted['normalized'],
                'memory_keyword' => $extracted['keyword'],
                'target_type' => $targetType,
                'category_id' => $correctedData['category_id'] ?? null,
                'wallet_id' => $correctedData['source_wallet_id'] ?? null,
                'weight' => $newWeight,
                'last_applied_at' => now(),
                'hit_count' => $newHitCount,
            ]);

            $memory->save();

            if ($transactionId !== null) {
                $exists = UserAiMemoryContribution::where('user_id', $userId)
                    ->where('transaction_id', $transactionId)
                    ->where('keyword', $keyword)
                    ->where('target_type', $targetType)
                    ->where('is_active', true)
                    ->exists();

                if (! $exists) {
                    UserAiMemoryContribution::create([
                        'memory_id' => $memory->id,
                        'user_id' => $userId,
                        'transaction_id' => $transactionId,
                        'source' => $source,
                        'keyword' => $keyword,
                        'target_type' => $targetType,
                        'target_id' => $targetType === 'category' ? ($correctedData['category_id'] ?? null) : ($correctedData['source_wallet_id'] ?? null),
                        'target_name' => null,
                        'weight_delta' => 1.0,
                        'is_active' => true,
                    ]);
                }
            }

            $action = $isNew ? 'CREATED' : 'REWARDED';
            $metadata = [
                'category_id' => $correctedData['category_id'] ?? null,
                'wallet_id' => $correctedData['source_wallet_id'] ?? null,
                'extractor_version' => 'v1',
                'decayed_from' => $oldWeight,
                'reward' => 1.0,
            ];

            $reasons = [];
            if ($isNew) {
                $reasons[] = 'New memory learned';
            } else {
                $reasons[] = 'Repeated successful transaction';
                if ($decayedWeight < $oldWeight) {
                    $reasons[] = sprintf(
                        'Decayed from %.2f to %.2f (%.0f days)',
                        $oldWeight, $decayedWeight,
                        now()->diffInDays($memory->last_applied_at ?? now())
                    );
                }
            }

            UserAiMemoryLog::create([
                'memory_id' => $memory->id,
                'user_id' => $userId,
                'action' => $action,
                'transaction_id' => $transactionId,
                'source' => $source,
                'raw_subject' => $extracted['raw'],
                'normalized_subject' => $extracted['normalized'],
                'memory_keyword' => $extracted['keyword'],
                'old_weight' => $isNew ? null : $oldWeight,
                'new_weight' => $newWeight,
                'old_hit_count' => $isNew ? null : $oldHitCount,
                'new_hit_count' => $newHitCount,
                'reason' => implode('; ', $reasons),
                'metadata' => $metadata,
                'algorithm_version' => 'v1-keyword',
            ]);

            // Unified activity
            ActivityLogger::log($userId, 'memory', strtolower($action), 'Memory '.strtolower($action).': '.$extracted['keyword'], implode('; ', $reasons).' (w:'.$newWeight.')', ['keyword' => $extracted['keyword'], 'source' => $source, 'weight' => $newWeight]);
        });

        $this->invalidateCache($userId);
    }

    public function learnFromKeywords(int $userId, array $keywords, ?string $source = null, ?int $transactionId = null): void
    {
        if (empty($keywords)) {
            return;
        }

        DB::transaction(function () use ($userId, $keywords, $source, $transactionId) {
            foreach ($keywords as $entry) {
                $keyword = trim($entry['keyword'] ?? '');
                $targetType = $entry['target_type'] ?? null;
                $targetId = $entry['target_id'] ?? null;
                $targetName = $entry['target_name'] ?? null;

                if (strlen($keyword) < 2 || ! in_array($targetType, ['category', 'wallet'], true)) {
                    continue;
                }

                if ($transactionId !== null) {
                    $exists = UserAiMemoryContribution::where('user_id', $userId)
                        ->where('transaction_id', $transactionId)
                        ->where('keyword', $keyword)
                        ->where('target_type', $targetType)
                        ->where('is_active', true)
                        ->exists();

                    if ($exists) {
                        continue;
                    }
                }

                $memory = UserAiMemory::firstOrNew([
                    'user_id' => $userId,
                    'keyword_pattern' => mb_strtolower($keyword),
                    'target_type' => $targetType,
                ]);

                $isNew = ! $memory->exists;
                $oldWeight = (float) ($memory->weight ?? 0.0);

                $decayedWeight = $memory->exists
                    ? $this->decayEngine->calculateDecayedWeight($oldWeight, $memory->last_applied_at ?? now())
                    : 0.0;

                $newWeight = min(5.0, $decayedWeight + 1.0);

                $fillData = [
                    'memory_keyword' => mb_strtolower($keyword),
                    'weight' => $newWeight,
                    'last_applied_at' => now(),
                ];

                if ($targetType === 'category' && $targetId !== null) {
                    $fillData['category_id'] = $targetId;
                }

                if ($targetType === 'wallet' && $targetId !== null) {
                    $fillData['wallet_id'] = $targetId;
                }

                $memory->fill($fillData);
                $memory->save();

                if ($isNew) {
                    $memory->hit_count = 1;
                } else {
                    DB::table('user_ai_memories')
                        ->where('id', $memory->id)
                        ->increment('hit_count');
                }

                UserAiMemoryContribution::create([
                    'memory_id' => $memory->id,
                    'user_id' => $userId,
                    'transaction_id' => $transactionId,
                    'source' => $source,
                    'keyword' => mb_strtolower($keyword),
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'target_name' => $targetName,
                    'weight_delta' => 1.0,
                    'is_active' => true,
                ]);
            }
        });

        $this->invalidateCache($userId);
    }

    private function invalidateCache(int $userId): void
    {
        Cache::forget("ai-mem-v2-{$userId}");
        Cache::forget("ai-mem-resolve-{$userId}");
    }

    public function revokeContributions(int $userId, int $transactionId): void
    {
        $contributions = UserAiMemoryContribution::where('user_id', $userId)
            ->where('transaction_id', $transactionId)
            ->where('is_active', true)
            ->get();

        if ($contributions->isEmpty()) {
            return;
        }

        $affectedMemoryIds = [];

        DB::transaction(function () use ($contributions, &$affectedMemoryIds) {
            foreach ($contributions as $contribution) {
                $contribution->update(['is_active' => false]);
                $affectedMemoryIds[] = $contribution->memory_id;
            }
        });

        foreach (array_unique($affectedMemoryIds) as $memoryId) {
            $this->rebuildMemoryWeight($memoryId);
        }

        $this->invalidateCache($userId);
    }

    public function syncTransactionMemory(int $userId, int $transactionId, array $newKeywords, ?string $source = null): void
    {
        $this->revokeContributions($userId, $transactionId);

        if (! empty($newKeywords)) {
            $this->learnFromKeywords($userId, $newKeywords, $source, $transactionId);
        }
    }

    private function rebuildMemoryWeight(int $memoryId): void
    {
        $memory = UserAiMemory::find($memoryId);
        if (! $memory) {
            return;
        }

        $activeContributions = $memory->activeContributions()->get();

        if ($activeContributions->isEmpty()) {
            UserAiMemoryLog::create([
                'memory_id' => $memory->id,
                'user_id' => $memory->user_id,
                'action' => 'PRUNED',
                'reason' => 'No active contributions remaining',
                'old_weight' => $memory->weight,
                'new_weight' => 0.0,
                'algorithm_version' => 'v2-provenance',
            ]);

            $memory->delete();

            return;
        }

        $newWeight = min(5.0, $activeContributions->sum('weight_delta'));

        UserAiMemoryLog::create([
            'memory_id' => $memory->id,
            'user_id' => $memory->user_id,
            'action' => 'REBUILT',
            'reason' => 'Rebuilt from '.$activeContributions->count().' active contributions',
            'old_weight' => $memory->weight,
            'new_weight' => $newWeight,
            'algorithm_version' => 'v2-provenance',
        ]);

        $memory->update([
            'weight' => $newWeight,
            'hit_count' => $activeContributions->count(),
        ]);
    }

    /**
     * Mengambil Top-N memori terkuat untuk disuntikkan ke Prompt AI (Sprint 4E.5).
     */
    public function getTopRelevantMemories(int $userId, string $inputText): array
    {
        $cacheKey = "ai-mem-v2-{$userId}";

        $memories = Cache::remember($cacheKey, 300, function () use ($userId) {
            return UserAiMemory::where('user_id', $userId)
                ->with(['category:id,category_name', 'wallet:id,name'])
                ->orderByDesc('weight')
                ->get();
        });

        if (! ($memories instanceof Collection)) {
            Cache::forget($cacheKey);
            $memories = UserAiMemory::where('user_id', $userId)
                ->with(['category:id,category_name', 'wallet:id,name'])
                ->orderByDesc('weight')
                ->get();
        }

        $matched = [];
        $textLower = mb_strtolower($inputText);

        foreach ($memories as $memory) {
            if (! ($memory instanceof UserAiMemory)) {
                continue;
            }

            $pattern = $memory->keyword_pattern;

            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            if (! $this->memoryContains($textLower, $pattern)) {
                continue;
            }

            $decayedWeight = $this->decayEngine->calculateDecayedWeight(
                (float) $memory->weight,
                $memory->last_applied_at ?? $memory->created_at
            );

            $matched[] = [
                'keyword' => $pattern,
                'category' => $memory->category?->category_name,
                'wallet' => $memory->wallet?->name,
                'target_type' => $memory->target_type,
                'effective_weight' => $decayedWeight,
                'hit_count' => $memory->hit_count,
            ];
        }

        usort($matched, fn ($a, $b) => $b['effective_weight'] <=> $a['effective_weight']);

        $limit = (int) config('bendaharaku.ai.memory.max_memories', 5);

        return array_slice($matched, 0, $limit);
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
