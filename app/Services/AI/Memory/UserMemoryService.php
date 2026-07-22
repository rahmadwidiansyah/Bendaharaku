<?php

declare(strict_types=1);

namespace App\Services\AI\Memory;

use App\Models\UserAiMemory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

readonly class UserMemoryService
{
    public function __construct(
        private MemoryDecayEngine $decayEngine
    ) {}

    /**
     * Membangun memori hanya dari entitas Subject (Pihak/Merchant) yang diverifikasi.
     */
    public function upsertMemory(int $userId, array $correctedData): void
    {
        $subject = $correctedData['subject'] ?? null;

        // Abaikan jika tidak ada subjek eksplisit. Kita tidak mau mengingat 'noise'
        if (blank($subject) || $subject === '-' || $subject === 'System') {
            return;
        }

        // Bersihkan hashtag jika ada (misal #Budi menjadi budi)
        $pattern = strtolower(trim(str_replace('#', '', $subject)));

        if (strlen($pattern) < 3) {
            return;
        }

        DB::transaction(function () use ($userId, $pattern, $correctedData) {
            $memory = UserAiMemory::firstOrNew([
                'user_id' => $userId,
                'keyword_pattern' => $pattern,
            ]);

            // Jika memori sudah ada, kalkulasi pelemahan bobotnya sejak terakhir dipakai, lalu tambah 1.0 (Reward)
            $currentWeight = $memory->exists
                ? $this->decayEngine->calculateDecayedWeight((float) $memory->weight, $memory->last_applied_at ?? now())
                : 0.0;

            $newWeight = min(5.0, $currentWeight + 1.0); // Cap bobot maksimal di 5.0

            $memory->fill([
                'category_id' => $correctedData['category_id'] ?? null,
                'wallet_id' => $correctedData['source_wallet_id'] ?? null,
                'weight' => $newWeight,
                'last_applied_at' => now(),
            ]);

            $memory->save();

            DB::table('user_ai_memories')
                ->where('id', $memory->id)
                ->increment('hit_count');
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

        // Validasi: Jika cache return bukan Collection (corrupt), clear dan re-fetch
        if (! ($memories instanceof Collection)) {
            Cache::forget($cacheKey);
            $memories = UserAiMemory::where('user_id', $userId)
                ->with('category:id,category_name')
                ->orderByDesc('weight')
                ->get();
        }

        $matched = [];
        $textLower = strtolower($inputText);

        // Filter: Hanya ambil memori yang keyword-nya benar-benar diucapkan user saat ini
        foreach ($memories as $memory) {
            // Guard ketat: pastikan ia betul-betul Eloquent model UserAiMemory
            // (bukan string, array, __PHP_Incomplete_Class, atau object lain)
            if (! ($memory instanceof UserAiMemory)) {
                continue;
            }

            $pattern = $memory->keyword_pattern;

            // Pastikan keyword_pattern adalah string yang valid
            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            if (preg_match("/\b".preg_quote(strtolower($pattern), '/')."\b/i", $textLower)) {
                $matched[] = [
                    'keyword' => $pattern,
                    'category' => $memory->category?->category_name,
                ];
            }
        }

        // Batasi maksimal 5 memori paling relevan agar token LLM tidak meledak
        $limit = (int) config('bendaharaku.ai.memory.max_memories', 5);

        return array_slice($matched, 0, $limit);
    }
}
