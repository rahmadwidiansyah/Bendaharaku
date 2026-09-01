<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserAiMemory;
use App\Models\UserAiMemoryContribution;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryModelRelationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    public function test_memory_has_target_type_column(): void
    {
        $memory = UserAiMemory::create([
            'user_id' => $this->user->id,
            'keyword_pattern' => 'bakso',
            'target_type' => 'category',
            'category_id' => $this->user->categories()->first()?->id,
            'weight' => 1.0,
            'hit_count' => 1,
        ]);

        self::assertSame('category', $memory->target_type);
        self::assertDatabaseHas('user_ai_memories', [
            'id' => $memory->id,
            'target_type' => 'category',
        ]);
    }

    public function test_memory_wallet_target_type(): void
    {
        $wallet = $this->user->wallets()->where('group_type', '!=', 'System')->first();

        $memory = UserAiMemory::create([
            'user_id' => $this->user->id,
            'keyword_pattern' => 'cash',
            'target_type' => 'wallet',
            'wallet_id' => $wallet?->id,
            'weight' => 1.0,
            'hit_count' => 1,
        ]);

        self::assertSame('wallet', $memory->target_type);
    }

    public function test_contribution_belongs_to_memory(): void
    {
        $memory = UserAiMemory::create([
            'user_id' => $this->user->id,
            'keyword_pattern' => 'bakso',
            'target_type' => 'category',
            'weight' => 1.0,
            'hit_count' => 1,
        ]);

        $contribution = UserAiMemoryContribution::create([
            'memory_id' => $memory->id,
            'user_id' => $this->user->id,
            'transaction_id' => 12345,
            'source' => 'telegram',
            'keyword' => 'bakso',
            'target_type' => 'category',
            'target_id' => 1,
            'target_name' => 'Makan & Minum',
            'weight_delta' => 1.0,
            'is_active' => true,
        ]);

        self::assertTrue($contribution->memory->is($memory));
        self::assertSame(12345, $contribution->transaction_id);
        self::assertTrue($contribution->is_active);
    }

    public function test_memory_has_many_contributions(): void
    {
        $memory = UserAiMemory::create([
            'user_id' => $this->user->id,
            'keyword_pattern' => 'grab',
            'target_type' => 'category',
            'weight' => 2.0,
            'hit_count' => 2,
        ]);

        UserAiMemoryContribution::create([
            'memory_id' => $memory->id,
            'user_id' => $this->user->id,
            'transaction_id' => 100,
            'keyword' => 'grab',
            'target_type' => 'category',
            'weight_delta' => 1.0,
            'is_active' => true,
        ]);

        UserAiMemoryContribution::create([
            'memory_id' => $memory->id,
            'user_id' => $this->user->id,
            'transaction_id' => 101,
            'keyword' => 'grab',
            'target_type' => 'category',
            'weight_delta' => 1.0,
            'is_active' => false,
        ]);

        self::assertCount(2, $memory->contributions);
        self::assertCount(1, $memory->activeContributions);
    }

    public function test_deleting_memory_cascades_contributions(): void
    {
        $memory = UserAiMemory::create([
            'user_id' => $this->user->id,
            'keyword_pattern' => 'indomaret',
            'target_type' => 'category',
            'weight' => 1.0,
            'hit_count' => 1,
        ]);

        UserAiMemoryContribution::create([
            'memory_id' => $memory->id,
            'user_id' => $this->user->id,
            'transaction_id' => 200,
            'keyword' => 'indomaret',
            'target_type' => 'category',
            'weight_delta' => 1.0,
            'is_active' => true,
        ]);

        $memoryId = $memory->id;
        $memory->delete();

        self::assertDatabaseMissing('user_ai_memory_contributions', ['memory_id' => $memoryId]);
    }
}
