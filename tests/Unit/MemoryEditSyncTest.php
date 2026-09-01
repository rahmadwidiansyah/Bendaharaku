<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\TransactionLog;
use App\Models\User;
use App\Models\UserAiMemory;
use App\Models\UserAiMemoryContribution;
use App\Services\AI\Memory\UserMemoryService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryEditSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private UserMemoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
        $this->service = app(UserMemoryService::class);
    }

    public function test_revoke_contributions_deactivates_and_rebuilds(): void
    {
        $category = $this->user->categories()->first();
        $wallet = $this->user->wallets()->where('group_type', '!=', 'System')->first();

        $transaction = TransactionLog::create([
            'user_id' => $this->user->id,
            'reference_number' => 'TRX-TEST1',
            'date' => now()->toDateString(),
            'type_id' => $category->type_id,
            'category_id' => $category->id,
            'source_wallet_id' => $wallet->id,
            'destination_wallet_id' => $wallet->id,
            'amount' => 20000,
            'balance_before' => 0,
            'balance_after' => 0,
            'subject' => 'bakso',
            'notes' => 'test',
            'is_cleared' => true,
        ]);

        $this->service->learnFromKeywords($this->user->id, [
            ['keyword' => 'bakso', 'target_type' => 'category', 'target_id' => $category->id, 'target_name' => $category->category_name],
            ['keyword' => 'cash', 'target_type' => 'wallet', 'target_id' => $wallet->id, 'target_name' => $wallet->name],
        ], 'telegram', $transaction->id);

        $memoryCategory = UserAiMemory::where('user_id', $this->user->id)->where('keyword_pattern', 'bakso')->first();
        $memoryWallet = UserAiMemory::where('user_id', $this->user->id)->where('keyword_pattern', 'cash')->first();

        $this->assertNotNull($memoryCategory);
        $this->assertNotNull($memoryWallet);

        $this->service->revokeContributions($this->user->id, $transaction->id);

        $this->assertSame(0, UserAiMemoryContribution::where('transaction_id', $transaction->id)->where('is_active', true)->count());

        $this->assertDatabaseMissing('user_ai_memories', [
            'user_id' => $this->user->id,
            'keyword_pattern' => 'bakso',
            'target_type' => 'category',
        ]);
        $this->assertDatabaseMissing('user_ai_memories', [
            'user_id' => $this->user->id,
            'keyword_pattern' => 'cash',
            'target_type' => 'wallet',
        ]);
    }

    public function test_sync_transaction_memory_replaces_old_with_new(): void
    {
        $category1 = $this->user->categories()->first();
        $category2 = $this->user->categories()->skip(1)->first();
        $wallet = $this->user->wallets()->where('group_type', '!=', 'System')->first();

        $transaction = TransactionLog::create([
            'user_id' => $this->user->id,
            'reference_number' => 'TRX-TEST2',
            'date' => now()->toDateString(),
            'type_id' => $category1->type_id,
            'category_id' => $category1->id,
            'source_wallet_id' => $wallet->id,
            'destination_wallet_id' => $wallet->id,
            'amount' => 30000,
            'balance_before' => 0,
            'balance_after' => 0,
            'subject' => 'bakso',
            'notes' => 'test',
            'is_cleared' => true,
        ]);

        $this->service->learnFromKeywords($this->user->id, [
            ['keyword' => 'bakso', 'target_type' => 'category', 'target_id' => $category1->id, 'target_name' => $category1->category_name],
        ], 'telegram', $transaction->id);

        $memory1 = UserAiMemory::where('user_id', $this->user->id)->where('keyword_pattern', 'bakso')->first();
        $this->assertNotNull($memory1);
        $this->assertSame($category1->id, $memory1->category_id);

        $this->service->syncTransactionMemory($this->user->id, $transaction->id, [
            ['keyword' => 'mie ayam', 'target_type' => 'category', 'target_id' => $category2->id, 'target_name' => $category2->category_name],
        ]);

        $this->assertNull(UserAiMemory::where('user_id', $this->user->id)->where('keyword_pattern', 'bakso')->first());

        $memory2 = UserAiMemory::where('user_id', $this->user->id)->where('keyword_pattern', 'mie ayam')->first();
        $this->assertNotNull($memory2);
        $this->assertSame($category2->id, $memory2->category_id);

        $contributions = UserAiMemoryContribution::where('transaction_id', $transaction->id)->where('is_active', true)->get();
        $this->assertSame(1, $contributions->count());
        $this->assertSame('mie ayam', $contributions->first()->keyword);
    }

    public function test_sync_with_empty_keywords_removes_all(): void
    {
        $category = $this->user->categories()->first();
        $wallet = $this->user->wallets()->where('group_type', '!=', 'System')->first();

        $transaction = TransactionLog::create([
            'user_id' => $this->user->id,
            'reference_number' => 'TRX-TEST3',
            'date' => now()->toDateString(),
            'type_id' => $category->type_id,
            'category_id' => $category->id,
            'source_wallet_id' => $wallet->id,
            'destination_wallet_id' => $wallet->id,
            'amount' => 15000,
            'balance_before' => 0,
            'balance_after' => 0,
            'subject' => 'indomie',
            'notes' => 'test',
            'is_cleared' => true,
        ]);

        $this->service->learnFromKeywords($this->user->id, [
            ['keyword' => 'indomie', 'target_type' => 'category', 'target_id' => $category->id, 'target_name' => $category->category_name],
        ], 'telegram', $transaction->id);

        $this->service->syncTransactionMemory($this->user->id, $transaction->id, []);

        $this->assertDatabaseCount('user_ai_memory_contributions', 0);
    }

    public function test_shared_memory_not_pruned_when_other_contributions_active(): void
    {
        $category = $this->user->categories()->first();
        $wallet = $this->user->wallets()->where('group_type', '!=', 'System')->first();

        $tx1 = TransactionLog::create([
            'user_id' => $this->user->id, 'reference_number' => 'TRX-S1', 'date' => now()->toDateString(),
            'type_id' => $category->type_id, 'category_id' => $category->id,
            'source_wallet_id' => $wallet->id, 'destination_wallet_id' => $wallet->id,
            'amount' => 10000, 'balance_before' => 0, 'balance_after' => 0,
            'subject' => 'grab', 'notes' => 'test', 'is_cleared' => true,
        ]);

        $tx2 = TransactionLog::create([
            'user_id' => $this->user->id, 'reference_number' => 'TRX-S2', 'date' => now()->toDateString(),
            'type_id' => $category->type_id, 'category_id' => $category->id,
            'source_wallet_id' => $wallet->id, 'destination_wallet_id' => $wallet->id,
            'amount' => 15000, 'balance_before' => 0, 'balance_after' => 0,
            'subject' => 'grab', 'notes' => 'test', 'is_cleared' => true,
        ]);

        $this->service->learnFromKeywords($this->user->id, [
            ['keyword' => 'grab', 'target_type' => 'category', 'target_id' => $category->id, 'target_name' => $category->category_name],
        ], 'telegram', $tx1->id);

        $this->service->learnFromKeywords($this->user->id, [
            ['keyword' => 'grab', 'target_type' => 'category', 'target_id' => $category->id, 'target_name' => $category->category_name],
        ], 'telegram', $tx2->id);

        $memory = UserAiMemory::where('user_id', $this->user->id)->where('keyword_pattern', 'grab')->first();
        $this->assertNotNull($memory);
        $this->assertSame(2, $memory->hit_count);

        $this->service->revokeContributions($this->user->id, $tx1->id);

        $memory->refresh();
        $this->assertNotNull($memory);
        $this->assertSame(1, $memory->hit_count);
        $this->assertSame(1.0, $memory->weight);
    }

    public function test_delete_transaction_removes_only_its_contributions(): void
    {
        $category = $this->user->categories()->first();
        $wallet = $this->user->wallets()->where('group_type', '!=', 'System')->first();

        $tx1 = TransactionLog::create([
            'user_id' => $this->user->id, 'reference_number' => 'TRX-D1', 'date' => now()->toDateString(),
            'type_id' => $category->type_id, 'category_id' => $category->id,
            'source_wallet_id' => $wallet->id, 'destination_wallet_id' => $wallet->id,
            'amount' => 10000, 'balance_before' => 0, 'balance_after' => 0,
            'subject' => 'bakso', 'notes' => 'test', 'is_cleared' => true,
        ]);

        $tx2 = TransactionLog::create([
            'user_id' => $this->user->id, 'reference_number' => 'TRX-D2', 'date' => now()->toDateString(),
            'type_id' => $category->type_id, 'category_id' => $category->id,
            'source_wallet_id' => $wallet->id, 'destination_wallet_id' => $wallet->id,
            'amount' => 20000, 'balance_before' => 0, 'balance_after' => 0,
            'subject' => 'bakso', 'notes' => 'test', 'is_cleared' => true,
        ]);

        $this->service->learnFromKeywords($this->user->id, [
            ['keyword' => 'bakso', 'target_type' => 'category', 'target_id' => $category->id, 'target_name' => $category->category_name],
        ], 'telegram', $tx1->id);

        $this->service->learnFromKeywords($this->user->id, [
            ['keyword' => 'bakso', 'target_type' => 'category', 'target_id' => $category->id, 'target_name' => $category->category_name],
        ], 'telegram', $tx2->id);

        $memory = UserAiMemory::where('user_id', $this->user->id)->where('keyword_pattern', 'bakso')->first();
        $this->assertSame(2, $memory->hit_count);

        $this->service->revokeContributions($this->user->id, $tx1->id);

        $memory->refresh();
        $this->assertNotNull($memory);
        $this->assertSame(1, $memory->hit_count);
    }
}
