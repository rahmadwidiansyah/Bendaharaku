<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvidenceStatsIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
        $this->other = User::factory()->create();
    }

    private function makeEvidence(User $user, string $status): void
    {
        DB::table('evidence')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'original_name' => 'struk.jpg',
            'stored_name' => 'struk.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 1024,
            'path' => 'evidence/test/struk.jpg',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_stats_only_counts_requesting_user()
    {
        $this->makeEvidence($this->user, 'READY');
        $this->makeEvidence($this->other, 'READY');
        $this->makeEvidence($this->other, 'READY');
        $this->makeEvidence($this->other, 'FAILED');

        $response = $this->actingAs($this->user)->getJson(route('chat.evidence.stats', ['hours' => 24]));
        $response->assertOk();
        $this->assertEquals(1, $response->json('total_evidence'));
        $this->assertEquals(1, $response->json('completed'));
        $this->assertEquals(0, $response->json('failed'));
    }

    public function test_stats_empty_for_user_without_evidence()
    {
        $this->makeEvidence($this->other, 'READY');

        $response = $this->actingAs($this->user)->getJson(route('chat.evidence.stats', ['hours' => 24]));
        $response->assertOk();
        $this->assertEquals(0, $response->json('total_evidence'));
    }
}
