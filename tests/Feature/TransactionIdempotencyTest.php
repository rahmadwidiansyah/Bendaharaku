<?php

namespace Tests\Feature;

use App\Models\TransactionLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TransactionIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    private function payload(int $amount = 50000): array
    {
        $liquid = $this->user->wallets()->where('group_type', 'Liquid')->orderBy('id')->firstOrFail();
        $sys = $this->user->wallets()->where('group_type', 'System')->orderBy('id')->firstOrFail();
        $cat = $this->user->categories()->whereHas('type', fn ($q) => $q->where('name', 'Expense'))->firstOrFail();

        return [
            'date' => now()->format('Y-m-d'),
            'category_id' => $cat->id,
            'source_wallet_id' => $liquid->id,
            'destination_wallet_id' => $sys->id,
            'amount' => $amount,
            'transaction_type' => 'expense',
            'subject' => 'Idem',
        ];
    }

    private function rowCount(): int
    {
        return TransactionLog::where('user_id', $this->user->id)->where('subject', 'IDEM')->count();
    }

    public function test_same_key_twice_creates_single_row()
    {
        $headers = ['Idempotency-Key' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'];

        $this->actingAs($this->user)->withHeaders($headers)
            ->post(route('transactions.store'), $this->payload())
            ->assertRedirect(route('dashboard'));
        $this->actingAs($this->user)->withHeaders($headers)
            ->post(route('transactions.store'), $this->payload())
            ->assertRedirect(route('dashboard'));

        $this->assertEquals(1, $this->rowCount());
    }

    public function test_different_keys_same_payload_create_separate_rows()
    {
        $this->actingAs($this->user)->withHeaders(['Idempotency-Key' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeee1'])
            ->post(route('transactions.store'), $this->payload());
        $this->actingAs($this->user)->withHeaders(['Idempotency-Key' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeee2'])
            ->post(route('transactions.store'), $this->payload());

        $this->assertEquals(2, $this->rowCount());
    }

    public function test_without_key_behaves_as_before()
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload());
        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload());

        $this->assertEquals(2, $this->rowCount());
    }

    public function test_failed_attempt_does_not_consume_key()
    {
        $headers = ['Idempotency-Key' => 'bbbbbbbb-cccc-dddd-eeee-ffffffffffff'];

        // Gagal validasi (amount 0) → kunci tidak tersentuh.
        $this->actingAs($this->user)->withHeaders($headers)
            ->post(route('transactions.store'), $this->payload(0))
            ->assertSessionHasErrors('amount');

        $this->actingAs($this->user)->withHeaders($headers)
            ->post(route('transactions.store'), $this->payload())
            ->assertRedirect(route('dashboard'));

        $this->assertEquals(1, $this->rowCount());
    }

    public function test_inflight_claim_asks_for_retry()
    {
        $key = hash('sha256', $this->user->id.':cccccccc-dddd-eeee-ffff-000000000000');
        Cache::put("idempotency:txn:{$key}:claim", true, 300);

        $this->actingAs($this->user)->withHeaders(['Idempotency-Key' => 'cccccccc-dddd-eeee-ffff-000000000000'])
            ->post(route('transactions.store'), $this->payload())
            ->assertSessionHas('error');

        $this->assertEquals(0, $this->rowCount());
    }
}
