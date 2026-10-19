<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Category;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\UserAiMemory;
use App\Services\AI\Memory\MemoryDecayEngine;
use App\Services\AI\Memory\UserMemoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMemoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private UserMemoryService $memoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->memoryService = app(UserMemoryService::class);
    }

    public function test_get_top_relevant_memories_returns_effective_weight(): void
    {
        $type = TransactionType::create(['name' => 'Expense']);
        $category = Category::create([
            'user_id' => $this->user->id,
            'category_name' => 'Makanan',
            'type_id' => $type->id,
        ]);

        UserAiMemory::create([
            'user_id' => $this->user->id,
            'keyword_pattern' => 'bakso',
            'category_id' => $category->id,
            'weight' => 5.0,
            'hit_count' => 1,
            'last_applied_at' => Carbon::now(),
        ]);

        $memories = $this->memoryService->getTopRelevantMemories(
            $this->user->id,
            'saya mau makan bakso'
        );

        $this->assertCount(1, $memories);
        $this->assertArrayHasKey('effective_weight', $memories[0]);
        $this->assertArrayHasKey('hit_count', $memories[0]);
        $this->assertSame('bakso', $memories[0]['keyword']);
        $this->assertSame('Makanan', $memories[0]['category']);
    }

    public function test_effective_weight_is_decayed(): void
    {
        $type = TransactionType::create(['name' => 'Expense']);
        $category = Category::create([
            'user_id' => $this->user->id,
            'category_name' => 'Makanan',
            'type_id' => $type->id,
        ]);

        UserAiMemory::create([
            'user_id' => $this->user->id,
            'keyword_pattern' => 'bakso',
            'category_id' => $category->id,
            'weight' => 5.0,
            'hit_count' => 1,
            'last_applied_at' => Carbon::now()->subDays(10),
        ]);

        $memories = $this->memoryService->getTopRelevantMemories(
            $this->user->id,
            'saya mau makan bakso'
        );

        $this->assertCount(1, $memories);
        $effectiveWeight = $memories[0]['effective_weight'];

        $decayEngine = new MemoryDecayEngine;
        $expected = $decayEngine->calculateDecayedWeight(5.0, Carbon::now()->subDays(10));

        $this->assertSame($expected, $effectiveWeight);
        $this->assertLessThanOrEqual(5.0, $effectiveWeight, 'Decayed weight should not exceed original');
    }

    public function test_returns_empty_when_no_keyword_match(): void
    {
        UserAiMemory::create([
            'user_id' => $this->user->id,
            'keyword_pattern' => 'bakso',
            'weight' => 5.0,
            'hit_count' => 1,
        ]);

        $memories = $this->memoryService->getTopRelevantMemories(
            $this->user->id,
            'saya mau transfer uang'
        );

        $this->assertCount(0, $memories);
    }

    public function test_limit_dihitung_per_target_type(): void
    {
        config()->set('bendaharaku.ai.memory.max_memories', 2);

        $type = TransactionType::create(['name' => 'Expense']);
        $category = Category::create([
            'user_id' => $this->user->id,
            'category_name' => 'Makanan',
            'type_id' => $type->id,
        ]);

        foreach (range(1, 3) as $i) {
            UserAiMemory::create([
                'user_id' => $this->user->id,
                'keyword_pattern' => "kopi{$i}",
                'target_type' => 'category',
                'category_id' => $category->id,
                'weight' => 5.0 - ($i * 0.1),
                'hit_count' => 1,
                'last_applied_at' => Carbon::now(),
            ]);
        }

        foreach (range(1, 3) as $i) {
            $wallet = $this->user->wallets()->create([
                'name' => "Dompet {$i}",
                'balance' => 0,
                'group_type' => 'Liquid',
            ]);

            UserAiMemory::create([
                'user_id' => $this->user->id,
                'keyword_pattern' => "dompet{$i}",
                'target_type' => 'wallet',
                'wallet_id' => $wallet->id,
                'weight' => 5.0 - ($i * 0.1),
                'hit_count' => 1,
                'last_applied_at' => Carbon::now(),
            ]);
        }

        $memories = $this->memoryService->getTopRelevantMemories(
            $this->user->id,
            'kopi1 kopi2 kopi3 dompet1 dompet2 dompet3'
        );

        $this->assertCount(4, $memories);

        $counts = array_count_values(array_column($memories, 'target_type'));
        $this->assertSame(2, $counts['category'] ?? 0);
        $this->assertSame(2, $counts['wallet'] ?? 0);
    }
}
