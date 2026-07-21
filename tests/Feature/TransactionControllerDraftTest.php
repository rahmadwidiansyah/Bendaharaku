<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Category;
use App\Models\TransactionType;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerDraftTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Wallet $sourceWallet;
    private Wallet $destWallet;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['name' => 'Budi']);
        
        // Hapus data starter agar clean
        $this->user->wallets()->forceDelete();
        $this->user->categories()->forceDelete();

        $expenseType = TransactionType::create(['name' => 'Expense']);
        
        $this->category = $this->user->categories()->create([
            'category_name' => 'Makan & Minum',
            'keyword'       => 'makan',
            'type_id'       => $expenseType->id,
        ]);

        $this->sourceWallet = $this->user->wallets()->create([
            'name'       => 'Dompet Cash',
            'keyword'    => 'cash',
            'balance'    => 100000,
            'group_type' => 'Liquid',
        ]);

        $this->destWallet = $this->user->wallets()->create([
            'name'       => 'Merchant System',
            'group_type' => 'System',
        ]);

        config(['bendaharaku.system_wallets.merchant' => 'Merchant System']);
    }

    /**
     * Test confirm draft via TransactionController@confirm
     */
    public function test_confirm_draft_via_controller()
    {
        $draft = TransactionDraft::create([
            'user_id'       => $this->user->id,
            'ai_provider'   => 'gemini',
            'ai_model'      => 'gemini-1.5-flash',
            'draft_type'    => 'single',
            'status'        => 'pending',
            'ai_confidence' => 0.90,
            'original_text' => 'beli bakso 15rb cash',
            'payload'       => [
                'amount'                  => 15000,
                'category_id'             => $this->category->id,
                'category_name'           => $this->category->category_name,
                'source_wallet_id'        => $this->sourceWallet->id,
                'source_wallet_name'      => $this->sourceWallet->name,
                'destination_wallet_id'   => $this->destWallet->id,
                'destination_wallet_name' => $this->destWallet->name,
                'subject'                 => 'Budi',
                'notes'                   => 'beli bakso 15rb cash',
                'type_key'                => 'expense',
                'needs_wallet'            => false,
                'is_cleared'              => false,
                'date'                    => now()->format('Y-m-d'),
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('transactions.confirm', $draft->id));

        $response->assertRedirect();
        
        // Assert draft status updated to confirmed
        $draft->refresh();
        $this->assertEquals('confirmed', $draft->status);

        // Assert transaction log created
        $this->assertDatabaseHas('transaction_logs', [
            'user_id' => $this->user->id,
            'amount'  => 15000,
        ]);

        $log = TransactionLog::latest('id')->firstOrFail();
        $this->assertContains($log->id, $draft->confirmed_transaction_ids);

        // Assert wallet balance updated
        $this->assertEquals(85000, $this->sourceWallet->fresh()->balance);
    }

    /**
     * Test cancel/delete draft via TransactionController@destroy
     */
    public function test_cancel_draft_via_controller()
    {
        $draft = TransactionDraft::create([
            'user_id'       => $this->user->id,
            'ai_provider'   => 'gemini',
            'ai_model'      => 'gemini-1.5-flash',
            'draft_type'    => 'single',
            'status'        => 'pending',
            'ai_confidence' => 0.90,
            'original_text' => 'beli bakso 15rb cash',
            'payload'       => [
                'amount'                  => 15000,
                'category_id'             => $this->category->id,
                'category_name'           => $this->category->category_name,
                'source_wallet_id'        => $this->sourceWallet->id,
                'source_wallet_name'      => $this->sourceWallet->name,
                'destination_wallet_id'   => $this->destWallet->id,
                'destination_wallet_name' => $this->destWallet->name,
                'subject'                 => 'Budi',
                'notes'                   => 'beli bakso 15rb cash',
                'type_key'                => 'expense',
                'needs_wallet'            => false,
                'is_cleared'              => false,
                'date'                    => now()->format('Y-m-d'),
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('transactions.destroy', $draft->id));

        $response->assertRedirect(route('dashboard'));

        // Assert draft is DELETED from transaction_drafts table
        $this->assertDatabaseMissing('transaction_drafts', [
            'id' => $draft->id,
        ]);

        // Assert no transaction log created
        $this->assertDatabaseCount('transaction_logs', 0);

        // Assert wallet balance remains the same
        $this->assertEquals(100000, $this->sourceWallet->fresh()->balance);
    }

    /**
     * Test edit draft renders edit form with draft payload mapped correctly
     */
    public function test_edit_draft_renders_edit_page()
    {
        $draft = TransactionDraft::create([
            'user_id'       => $this->user->id,
            'ai_provider'   => 'gemini',
            'ai_model'      => 'gemini-1.5-flash',
            'draft_type'    => 'single',
            'status'        => 'pending',
            'ai_confidence' => 0.90,
            'original_text' => 'beli bakso 15rb cash',
            'payload'       => [
                'amount'                  => 15000,
                'category_id'             => $this->category->id,
                'category_name'           => $this->category->category_name,
                'source_wallet_id'        => $this->sourceWallet->id,
                'source_wallet_name'      => $this->sourceWallet->name,
                'destination_wallet_id'   => $this->destWallet->id,
                'destination_wallet_name' => $this->destWallet->name,
                'subject'                 => 'Budi',
                'notes'                   => 'beli bakso 15rb cash',
                'type_key'                => 'expense',
                'needs_wallet'            => false,
                'is_cleared'              => false,
                'date'                    => '2026-07-22',
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.edit', $draft->id));

        $response->assertOk();

        // Check Inertia response contains mapped transaction data matching draft payload
        $response->assertInertia(fn ($page) => $page
            ->component('Transactions/Edit')
            ->has('transaction', fn ($trx) => $trx
                ->where('id', $draft->id)
                ->where('is_draft', true)
                ->where('amount', 15000)
                ->where('notes', 'beli bakso 15rb cash')
                ->where('subject', 'Budi')
                ->where('date', '2026-07-22')
                ->where('category_id', $this->category->id)
                ->where('source_wallet_id', $this->sourceWallet->id)
                ->where('destination_wallet_id', $this->destWallet->id)
                ->etc()
            )
        );
    }

    /**
     * Test update draft finalizes the transaction, updating wallets and logging transaction
     */
    public function test_update_draft_finalizes_transaction()
    {
        $draft = TransactionDraft::create([
            'user_id'       => $this->user->id,
            'ai_provider'   => 'gemini',
            'ai_model'      => 'gemini-1.5-flash',
            'draft_type'    => 'single',
            'status'        => 'pending',
            'ai_confidence' => 0.90,
            'original_text' => 'beli bakso 15rb cash',
            'payload'       => [
                'amount'                  => 15000,
                'category_id'             => $this->category->id,
                'category_name'           => $this->category->category_name,
                'source_wallet_id'        => $this->sourceWallet->id,
                'source_wallet_name'      => $this->sourceWallet->name,
                'destination_wallet_id'   => $this->destWallet->id,
                'destination_wallet_name' => $this->destWallet->name,
                'subject'                 => 'Budi',
                'notes'                   => 'beli bakso 15rb cash',
                'type_key'                => 'expense',
                'needs_wallet'            => false,
                'is_cleared'              => false,
                'date'                    => '2026-07-22',
            ],
        ]);

        // Simulated user edits data in form: changes amount to 20000 and notes to "beli bakso jumbo"
        $payload = [
            'date'                  => '2026-07-22',
            'category_id'           => $this->category->id,
            'source_wallet_id'      => $this->sourceWallet->id,
            'destination_wallet_id' => $this->destWallet->id,
            'amount'                => 20000,
            'transaction_type'      => 'expense',
            'subject'               => 'BUDI',
            'notes'                 => 'beli bakso jumbo',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('transactions.update', $draft->id), $payload);

        $response->assertRedirect(route('dashboard'));

        // Assert draft is confirmed
        $draft->refresh();
        $this->assertEquals('confirmed', $draft->status);

        // Assert log is created with updated values
        $this->assertDatabaseHas('transaction_logs', [
            'user_id'    => $this->user->id,
            'amount'     => 20000,
            'notes'      => 'beli bakso jumbo',
            'is_cleared' => true,
        ]);

        $log = TransactionLog::latest('id')->firstOrFail();
        $this->assertContains($log->id, $draft->confirmed_transaction_ids);

        // Assert wallet mutated by the new amount (100000 - 20000 = 80000)
        $this->assertEquals(80000, $this->sourceWallet->fresh()->balance);
    }
}
