<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Category;
use App\Models\TransactionType;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Services\Chat\DraftConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DraftConfirmationServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Wallet $sourceWallet;
    private Wallet $destWallet;
    private Category $category;
    private DraftConfirmationService $draftService;

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

        $this->draftService = $this->app->make(DraftConfirmationService::class);
    }

    /** @test */
    public function test_it_creates_a_draft_and_does_not_mutate_balance()
    {
        // 1. Buat Draft
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

        // Assert draft terekam di staging DB
        $this->assertDatabaseHas('transaction_drafts', [
            'id'     => $draft->id,
            'status' => 'pending',
        ]);

        // Assert tidak ada transaksi real
        $this->assertDatabaseCount('transaction_logs', 0);

        // Assert saldo wallet tidak berubah (tetap 100.000)
        $this->assertEquals(100000, $this->sourceWallet->fresh()->balance);
    }

    /** @test */
    public function test_it_confirms_a_pending_draft_mutates_balance_and_saves_to_transaction_logs()
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

        // Konfirmasi draft
        $log = $this->draftService->confirm($draft, $this->user);

        // Assert log transaksi dibuat
        $this->assertInstanceOf(TransactionLog::class, $log);
        $this->assertTrue($log->is_cleared);
        $this->assertEquals(15000, $log->amount);

        // Assert draft berstatus confirmed dan menyimpan referensi ID transaksi log
        $draft->refresh();
        $this->assertEquals('confirmed', $draft->status);
        $this->assertContains($log->id, $draft->confirmed_transaction_ids);

        // Assert saldo berkurang (100.000 - 15.000 = 85.000)
        $this->assertEquals(85000, $this->sourceWallet->fresh()->balance);
    }

    /** @test */
    public function test_it_guarantees_idempotency_when_confirming_multiple_times()
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

        // Konfirmasi ke-1
        $log1 = $this->draftService->confirm($draft, $this->user);

        // Konfirmasi ke-2 (idempotency check)
        $log2 = $this->draftService->confirm($draft, $this->user);

        // Assert mengembalikan objek log yang sama persis
        $this->assertEquals($log1->id, $log2->id);

        // Assert database hanya mencatat SATU log transaksi (mencegah duplikasi)
        $this->assertDatabaseCount('transaction_logs', 1);

        // Assert saldo berkurang hanya sekali (85.000, bukan 70.000)
        $this->assertEquals(85000, $this->sourceWallet->fresh()->balance);
    }

    /** @test */
    public function test_it_cancels_a_pending_draft_and_does_not_create_transaction_log()
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

        // Batalkan draft
        $this->draftService->cancel($draft, $this->user);

        // Assert status draft berubah menjadi cancelled
        $this->assertEquals('cancelled', $draft->fresh()->status);

        // Assert tidak ada transaksi yang dibuat di database
        $this->assertDatabaseCount('transaction_logs', 0);

        // Assert saldo tetap aman
        $this->assertEquals(100000, $this->sourceWallet->fresh()->balance);
    }
}
