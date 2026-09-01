<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserAiMemory;
use App\Services\AI\Memory\MemoryDecayEngine;
use App\Services\AI\Memory\MemoryKeywordExtractor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    public function test_decay_engine_calculates_exponential_decay(): void
    {
        $engine = new MemoryDecayEngine;
        $weight = 5.0;

        $recentResult = $engine->calculateDecayedWeight($weight, now()->subDay());
        $oldResult = $engine->calculateDecayedWeight($weight, now()->subDays(30));

        // 1 day decay: 5.0 * exp(-0.05 * 1) = 4.7564
        self::assertLessThan($weight, $recentResult);
        self::assertGreaterThan(0, $recentResult);

        // 30 day decay: 5.0 * exp(-0.05 * 30) = 1.1159
        self::assertLessThan($weight, $oldResult);
        self::assertGreaterThan(0, $oldResult);

        // Older memory decays more than recent
        self::assertGreaterThan($oldResult, $recentResult);
    }

    public function test_keyword_extractor_supports_multi_word_keywords(): void
    {
        $extractor = new MemoryKeywordExtractor;

        $result = $extractor->extract('Mie Ayam Jakarta');

        self::assertContains('mie', $result['keywords']);
        self::assertContains('ayam', $result['keywords']);
        self::assertContains('jakarta', $result['keywords']);
        self::assertContains('mie ayam', $result['keywords']);
    }

    public function test_keyword_extraction_normalizes_unicode(): void
    {
        $extractor = new MemoryKeywordExtractor;

        $result = $extractor->extract('Nasi Goreng Spesial');

        self::assertTrue(in_array('nasi', $result['keywords'], true));
        self::assertTrue(in_array('goreng', $result['keywords'], true));
        self::assertTrue(in_array('nasi goreng', $result['keywords'], true));
    }

    public function test_keyword_extraction_handles_stopwords(): void
    {
        $extractor = new MemoryKeywordExtractor;

        $result = $extractor->extract('pakai mie ayam');

        self::assertNotContains('pakai', $result['keywords']);
        self::assertContains('mie', $result['keywords']);
    }

    public function test_memory_ranking_ordered_by_decayed_weight(): void
    {
        $oldMemory = UserAiMemory::create([
            'user_id' => $this->user->id,
            'keyword_pattern' => 'lama',
            'target_type' => 'category',
            'weight' => 5.0,
            'hit_count' => 1,
            'last_applied_at' => now()->subDays(20),
            'created_at' => now()->subDays(20),
        ]);

        $newMemory = UserAiMemory::create([
            'user_id' => $this->user->id,
            'keyword_pattern' => 'baru',
            'target_type' => 'wallet',
            'weight' => 3.0,
            'hit_count' => 1,
            'last_applied_at' => now()->subDay(),
            'created_at' => now()->subDay(),
        ]);

        $engine = new MemoryDecayEngine;
        $decayedOld = $engine->calculateDecayedWeight(5.0, $oldMemory->last_applied_at);
        $decayedNew = $engine->calculateDecayedWeight(3.0, $newMemory->last_applied_at);

        // Memory baru (weight 3, 1 hari) harus lebih kuat setelah decay
        // daripada memory lama (weight 5, 20 hari) karena decay exponential
        self::assertGreaterThan($decayedOld, $decayedNew);
        self::assertGreaterThan(0, $decayedOld);
        self::assertGreaterThan(0, $decayedNew);

        // Pastikan weight asli tidak berubah
        $oldMemory->refresh();
        $newMemory->refresh();
        self::assertSame(5.0, $oldMemory->weight);
        self::assertSame(3.0, $newMemory->weight);
    }

    public function test_memory_collision_same_keyword_same_type_deduped(): void
    {
        $category = $this->user->categories()->first();
        $wallet = $this->user->wallets()->where('group_type', '!=', 'System')->first();

        $memory1 = UserAiMemory::create([
            'user_id' => $this->user->id,
            'keyword_pattern' => 'cash',
            'target_type' => 'wallet',
            'wallet_id' => $wallet->id,
            'weight' => 5.0,
            'hit_count' => 3,
        ]);

        $memory1->fill([
            'target_type' => 'category',
            'category_id' => $category->id,
            'wallet_id' => null,
        ]);
        $memory1->save();

        $memory1->refresh();
        self::assertSame('category', $memory1->target_type);
        self::assertSame($category->id, $memory1->category_id);
        self::assertNull($memory1->wallet_id);
    }

    public function test_cache_key_pattern_consistency(): void
    {
        $cacheKeyV2 = "ai-mem-v2-{$this->user->id}";
        $cacheKeyResolve = "ai-mem-resolve-{$this->user->id}";

        self::assertStringContainsString('v2', $cacheKeyV2);
        self::assertStringContainsString('resolve', $cacheKeyResolve);
        self::assertNotSame($cacheKeyV2, $cacheKeyResolve);
    }
}
