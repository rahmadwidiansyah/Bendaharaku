<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    private function storePayload(): array
    {
        $liquid = $this->user->wallets()->where('group_type', 'Liquid')->orderBy('id')->firstOrFail();
        $sys = $this->user->wallets()->where('group_type', 'System')->orderBy('id')->firstOrFail();
        $cat = $this->user->categories()->whereHas('type', fn ($q) => $q->where('name', 'Expense'))->firstOrFail();

        return [
            'date' => now()->format('Y-m-d'),
            'category_id' => $cat->id,
            'source_wallet_id' => $liquid->id,
            'destination_wallet_id' => $sys->id,
            'amount' => 10000,
            'transaction_type' => 'expense',
            'subject' => 'Rate',
        ];
    }

    public function test_transaction_store_throttled_after_60_per_minute()
    {
        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($this->user)->post(route('transactions.store'), $this->storePayload());
        }

        $this->actingAs($this->user)->post(route('transactions.store'), $this->storePayload())
            ->assertStatus(429);
    }

    public function test_evidence_store_throttled_after_30_per_minute()
    {
        // fake()->image() butuh ekstensi GD (tidak ada di env ini) → create().
        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($this->user)->post(route('chat.evidence.store'), [
                'image' => UploadedFile::fake()->create('struk.jpg', 100, 'image/jpeg'),
            ]);
        }

        $this->actingAs($this->user)->post(route('chat.evidence.store'), [
            'image' => UploadedFile::fake()->create('struk.jpg', 100, 'image/jpeg'),
        ])->assertStatus(429);
    }

    public function test_telegram_webhook_throttled_after_120_per_minute()
    {
        for ($i = 0; $i < 120; $i++) {
            $this->postJson('/api/telegram/webhook', ['update_id' => $i]);
        }

        $this->postJson('/api/telegram/webhook', ['update_id' => 999])
            ->assertStatus(429);
    }

    public function test_budget_generate_throttled_after_10_per_hour()
    {
        // Fake HTTP agar 10 generate pertama gagal-cepat tanpa memanggil LLM nyata.
        Http::fake();
        $now = now();
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->user)->postJson(route('budget.generate'), [
                'year' => (int) $now->format('Y'), 'month' => (int) $now->format('m'),
            ]);
        }

        $this->actingAs($this->user)->postJson(route('budget.generate'), [
            'year' => (int) $now->format('Y'), 'month' => (int) $now->format('m'),
        ])->assertStatus(429);
    }
}
