<?php

namespace Tests\Feature;

use App\Models\BudgetGroup;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OwnershipValidationTest extends TestCase
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

    public function test_transaction_store_rejects_other_users_wallet_with_422()
    {
        $foreignWallet = $this->other->wallets()->where('group_type', 'Liquid')->orderBy('id')->firstOrFail();
        $ownWallet = $this->user->wallets()->where('group_type', 'Liquid')->orderBy('id')->firstOrFail();
        $category = $this->user->categories()->whereHas('type', fn ($q) => $q->where('name', 'Expense'))->firstOrFail();

        $response = $this->actingAs($this->user)->post(route('transactions.store'), [
            'date' => now()->format('Y-m-d'),
            'category_id' => $category->id,
            'source_wallet_id' => $foreignWallet->id,
            'destination_wallet_id' => $ownWallet->id,
            'amount' => 50000,
            'transaction_type' => 'expense',
            'subject' => 'X',
        ]);

        // 422 validasi (konsisten), bukan 404/500 — dan tidak ada baris tercipta.
        $response->assertSessionHasErrors('source_wallet_id');
        $this->assertDatabaseMissing('transaction_logs', ['user_id' => $this->user->id, 'amount' => 50000]);
    }

    public function test_transaction_store_rejects_other_users_category_with_422()
    {
        $foreignCategory = $this->other->categories()->whereHas('type', fn ($q) => $q->where('name', 'Expense'))->firstOrFail();
        $ownWallet = $this->user->wallets()->where('group_type', 'Liquid')->orderBy('id')->firstOrFail();
        $sysWallet = $this->user->wallets()->where('group_type', 'System')->orderBy('id')->firstOrFail();

        $response = $this->actingAs($this->user)->post(route('transactions.store'), [
            'date' => now()->format('Y-m-d'),
            'category_id' => $foreignCategory->id,
            'source_wallet_id' => $ownWallet->id,
            'destination_wallet_id' => $sysWallet->id,
            'amount' => 50000,
            'transaction_type' => 'expense',
            'subject' => 'X',
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseMissing('transaction_logs', ['user_id' => $this->user->id, 'amount' => 50000]);
    }

    public function test_budget_update_rejects_item_from_other_group_with_422()
    {
        $group = BudgetGroup::create([
            'user_id' => $this->user->id, 'period_month' => 8, 'period_year' => 2026,
            'total_budget_amount' => 0, 'generated_by' => 'manual',
        ]);
        $category = $this->user->categories()->firstOrFail();
        $ownItem = $group->items()->create([
            'budgetable_id' => $category->id, 'budgetable_type' => Category::class, 'target_amount' => 100000,
        ]);
        $otherGroup = BudgetGroup::create([
            'user_id' => $this->user->id, 'period_month' => 9, 'period_year' => 2026,
            'total_budget_amount' => 0, 'generated_by' => 'manual',
        ]);
        $foreignItem = $otherGroup->items()->create([
            'budgetable_id' => $category->id, 'budgetable_type' => Category::class, 'target_amount' => 50000,
        ]);

        $this->actingAs($this->user)->putJson("/api/v1/budget/{$group->id}", [
            'items' => [
                ['id' => $ownItem->id, 'target_amount' => 120000],
                ['id' => $foreignItem->id, 'target_amount' => 1],
            ],
        ])->assertStatus(422);

        $this->assertEquals(100000, (float) $ownItem->fresh()->target_amount);
        $this->assertEquals(50000, (float) $foreignItem->fresh()->target_amount);
    }

    public function test_evidence_review_rejects_other_users_wallet_with_422()
    {
        $uuid = '11111111-2222-4333-8444-555555555555';
        DB::table('evidence')->insert([
            'uuid' => $uuid,
            'user_id' => $this->user->id,
            'original_name' => 's.jpg',
            'stored_name' => 's.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 10,
            'path' => 'evidence/t/s.jpg',
            'status' => 'READY',
            'resolved_data' => json_encode(['amount' => 10000]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $foreignWallet = $this->other->wallets()->where('group_type', 'Liquid')->orderBy('id')->firstOrFail();

        $this->actingAs($this->user)
            ->patchJson(route('chat.evidence.draft.update', ['uuid' => $uuid]), ['wallet_id' => $foreignWallet->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('wallet_id');
    }
}
