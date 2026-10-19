<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAiMemory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneAiMemoriesCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function memory(array $attributes = []): UserAiMemory
    {
        return UserAiMemory::create(array_merge([
            'user_id' => $this->user->id,
            'keyword_pattern' => 'bakso',
            'memory_keyword' => 'bakso',
            'target_type' => 'category',
            'hit_count' => 1,
            'weight' => 5.0,
            'last_applied_at' => now()->subDays(10),
        ], $attributes));
    }

    public function test_memory_terbukti_lewat_hit_count_tidak_dihapus(): void
    {
        $memory = $this->memory([
            'keyword_pattern' => 'sering',
            'memory_keyword' => 'sering',
            'hit_count' => 3,
            'weight' => 0.5,
            'last_applied_at' => now()->subDays(60),
        ]);

        $this->artisan('ai:prune-memories')->assertSuccessful();

        $this->assertDatabaseHas('user_ai_memories', ['id' => $memory->id]);
        $this->assertEqualsWithDelta(1.0, (float) $memory->fresh()->weight, 0.0001);
        $this->assertDatabaseHas('user_ai_memory_logs', [
            'memory_id' => $memory->id,
            'action' => 'PROTECTED',
        ]);
    }

    public function test_memory_terbukti_lewat_bobot_tidak_dihapus(): void
    {
        $memory = $this->memory([
            'keyword_pattern' => 'berbobot',
            'memory_keyword' => 'berbobot',
            'hit_count' => 1,
            'weight' => 3.5,
            'last_applied_at' => now()->subDays(60),
        ]);

        $this->artisan('ai:prune-memories')->assertSuccessful();

        $this->assertDatabaseHas('user_ai_memories', ['id' => $memory->id]);
        $this->assertEqualsWithDelta(1.0, (float) $memory->fresh()->weight, 0.0001);
        $this->assertDatabaseHas('user_ai_memory_logs', [
            'memory_id' => $memory->id,
            'action' => 'PROTECTED',
        ]);
    }

    public function test_memory_lemah_di_bawah_threshold_dihapus(): void
    {
        $memory = $this->memory([
            'keyword_pattern' => 'lemah',
            'memory_keyword' => 'lemah',
            'hit_count' => 1,
            'weight' => 0.5,
            'last_applied_at' => now()->subDays(60),
        ]);

        $this->artisan('ai:prune-memories')->assertSuccessful();

        $this->assertDatabaseMissing('user_ai_memories', ['id' => $memory->id]);
        $this->assertDatabaseHas('user_ai_memory_logs', [
            'user_id' => $this->user->id,
            'memory_keyword' => 'lemah',
            'action' => 'PRUNED',
        ]);
    }

    public function test_memory_lemah_di_atas_threshold_hanya_decay(): void
    {
        $memory = $this->memory(['hit_count' => 1, 'weight' => 2.5, 'last_applied_at' => now()->subDays(10)]);

        $this->artisan('ai:prune-memories')->assertSuccessful();

        $fresh = $memory->fresh();
        $this->assertNotNull($fresh);
        $this->assertLessThan(2.5, (float) $fresh->weight);
        $this->assertGreaterThan(0.2, (float) $fresh->weight);
        $this->assertDatabaseHas('user_ai_memory_logs', [
            'memory_id' => $memory->id,
            'action' => 'DECAYED',
        ]);
    }

    public function test_ambang_proteksi_konfigurabel(): void
    {
        config()->set('bendaharaku.ai.memory.protect_min_hits', 10);
        config()->set('bendaharaku.ai.memory.protect_min_weight', 9.0);

        $memory = $this->memory([
            'keyword_pattern' => 'turun-pangkat',
            'memory_keyword' => 'turun-pangkat',
            'hit_count' => 3,
            'weight' => 0.5,
            'last_applied_at' => now()->subDays(60),
        ]);

        $this->artisan('ai:prune-memories')->assertSuccessful();

        $this->assertDatabaseMissing('user_ai_memories', ['id' => $memory->id]);
        $this->assertDatabaseHas('user_ai_memory_logs', [
            'user_id' => $this->user->id,
            'memory_keyword' => 'turun-pangkat',
            'action' => 'PRUNED',
        ]);
    }
}
