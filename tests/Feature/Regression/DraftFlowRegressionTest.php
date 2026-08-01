<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Services\Chat\DraftConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftFlowRegressionTest extends TestCase
{
    use RefreshDatabase;
    use RegressionTestHelpers;

    private DraftConfirmationService $draftService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRegressionData();
        $this->draftService = $this->app->make(DraftConfirmationService::class);
    }

    public function test_create_draft_does_not_mutate_balance(): void
    {
        $draft = $this->makeDraft();

        $this->assertDatabaseHas('transaction_drafts', ['id' => $draft->id, 'status' => 'pending']);
        $this->assertDatabaseCount('transaction_logs', 0);
        $this->assertBalanceEquals($this->cashWallet, 500000);
    }

    public function test_confirm_draft_creates_transaction_and_deducts_balance(): void
    {
        $draft = $this->makeDraft();

        $transaction = $this->draftService->confirm($draft, $this->user);

        $this->assertInstanceOf(TransactionLog::class, $transaction);
        $this->assertEquals(15000, $transaction->amount);
        $this->assertTrue($transaction->is_cleared);
        $this->assertDatabaseHas('transaction_logs', ['id' => $transaction->id]);
        $this->assertDatabaseHas('transaction_drafts', ['id' => $draft->id, 'status' => 'confirmed']);
        $this->assertBalanceEquals($this->cashWallet, 485000);
    }

    public function test_confirm_draft_is_idempotent(): void
    {
        $draft = $this->makeDraft();

        $first = $this->draftService->confirm($draft, $this->user);
        $second = $this->draftService->confirm($draft, $this->user);

        $this->assertEquals($first->id, $second->id);
        $this->assertDatabaseCount('transaction_logs', 1);
        $this->assertBalanceEquals($this->cashWallet, 485000);
    }

    public function test_cancel_draft_marks_as_cancelled(): void
    {
        $draft = $this->makeDraft();

        $this->draftService->cancel($draft, $this->user);

        $this->assertDatabaseHas('transaction_drafts', ['id' => $draft->id, 'status' => 'cancelled']);
        $this->assertDatabaseCount('transaction_logs', 0);
        $this->assertBalanceEquals($this->cashWallet, 500000);
    }

    public function test_assign_wallet_to_draft_then_confirm(): void
    {
        $payload = $this->makeDraft()->payload;
        $payload['source_wallet_id'] = null;
        $payload['source_wallet_name'] = null;
        $payload['needs_wallet'] = true;

        $draft = $this->makeDraft(['payload' => $payload]);

        $transaction = $this->draftService->assignWallet($draft, $this->user, $this->cashWallet->id);

        $this->assertInstanceOf(TransactionLog::class, $transaction);
        $this->assertEquals(15000, $transaction->amount);
        $this->assertEquals($this->cashWallet->id, $transaction->source_wallet_id);
        $this->assertDatabaseHas('transaction_drafts', ['id' => $draft->id, 'status' => 'confirmed']);
    }

    public function test_confirm_draft_with_expense_deducts_merchant_as_destination(): void
    {
        $draft = $this->makeDraft();

        $transaction = $this->draftService->confirm($draft, $this->user);

        $this->assertEquals($this->cashWallet->id, $transaction->source_wallet_id);
        $this->assertEquals($this->merchantWallet->id, $transaction->destination_wallet_id);
    }

    public function test_format_draft_returns_structured_array(): void
    {
        $draft = $this->makeDraft();

        $formatted = $this->draftService->formatDraftForChat($draft);

        $this->assertIsArray($formatted);
        $this->assertArrayHasKey('draft_id', $formatted);
        $this->assertArrayHasKey('amount', $formatted);
        $this->assertArrayHasKey('category_name', $formatted);
        $this->assertArrayHasKey('source_wallet_name', $formatted);
        $this->assertArrayHasKey('status', $formatted);
    }

    public function test_multi_item_draft_confirm_creates_multiple_transactions(): void
    {
        $draft = TransactionDraft::create([
            'user_id' => $this->user->id,
            'ai_provider' => 'gemini',
            'ai_model' => 'gemini-1.5-flash',
            'draft_type' => 'multi',
            'status' => 'pending',
            'ai_confidence' => 0.85,
            'original_text' => 'beli bakso 15rb dan gajian 5jt',
            'payload' => [
                'items' => [
                    [
                        'amount' => 15000,
                        'category_id' => $this->foodCategory->id,
                        'category_name' => $this->foodCategory->category_name,
                        'source_wallet_id' => $this->cashWallet->id,
                        'source_wallet_name' => $this->cashWallet->name,
                        'destination_wallet_id' => $this->merchantWallet->id,
                        'destination_wallet_name' => $this->merchantWallet->name,
                        'subject' => 'Budi',
                        'notes' => 'beli bakso',
                        'type_key' => 'expense',
                        'needs_wallet' => false,
                        'is_cleared' => false,
                    ],
                    [
                        'amount' => 5000000,
                        'category_id' => $this->salaryCategory->id,
                        'category_name' => $this->salaryCategory->category_name,
                        'subject' => 'Budi',
                        'notes' => 'gajian',
                        'type_key' => 'income',
                        'needs_wallet' => false,
                        'is_cleared' => false,
                    ],
                ],
            ],
        ]);

        $transaction = $this->draftService->confirm($draft, $this->user);

        $this->assertInstanceOf(TransactionLog::class, $transaction);
        $this->assertDatabaseHas('transaction_drafts', ['id' => $draft->id, 'status' => 'confirmed']);
    }
}
