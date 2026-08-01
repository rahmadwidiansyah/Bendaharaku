<?php

declare(strict_types=1);

namespace App\Services\AI\Memory;

use App\Models\UserAiMemory;
use App\Models\UserAiMemoryLog;
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

        DB::transaction(function () use ($userId, $keyword, $extracted, $correctedData, $source, $transactionId) {
            $memory = UserAiMemory::firstOrNew([
                'user_id' => $userId,
                'keyword_pattern' => $keyword,
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
                'category_id' => $correctedData['category_id'] ?? null,
                'wallet_id' => $correctedData['source_wallet_id'] ?? null,
                'weight' => $newWeight,
                'last_applied_at' => now(),
            ]);

            $memory->save();

            DB::table('user_ai_memories')
                ->where('id', $memory->id)
                ->increment('hit_count');

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
        });
    }

    /**
     * Mengambil Top-N memori terkuat untuk disuntikkan ke Prompt AI (Sprint 4E.5).
     */
    public function getTopRelevantMemories(int $userId, string $inputText): array
    {
        $cacheKey = "ai-mem-v2-{$userId}";

        $memories = Cache::remember($cacheKey, 300, function () use ($userId) {
            return UserAiMemory::where('user_id', $userId)
                ->with('category:id,category_name')
                ->orderByDesc('weight')
                ->get();
        });

        if (! ($memories instanceof Collection)) {
            Cache::forget($cacheKey);
            $memories = UserAiMemory::where('user_id', $userId)
                ->with('category:id,category_name')
                ->orderByDesc('weight')
                ->get();
        }

        $matched = [];
        $textLower = strtolower($inputText);

        foreach ($memories as $memory) {
            if (! ($memory instanceof UserAiMemory)) {
                continue;
            }

            $pattern = $memory->keyword_pattern;

            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            if (preg_match("/\b".preg_quote(strtolower($pattern), '/')."\b/i", $textLower)) {
                $decayedWeight = $this->decayEngine->calculateDecayedWeight(
                    (float) $memory->weight,
                    $memory->last_applied_at ?? $memory->created_at
                );

                $matched[] = [
                    'keyword' => $pattern,
                    'category' => $memory->category?->category_name,
                    'effective_weight' => $decayedWeight,
                    'hit_count' => $memory->hit_count,
                ];
            }
        }

        $limit = (int) config('bendaharaku.ai.memory.max_memories', 5);

        return array_slice($matched, 0, $limit);
    }
}
