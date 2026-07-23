<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use App\DTO\AIParseResult;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Models\Category;
use App\Models\TransactionDraft;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Wallet;

trait RegressionTestHelpers
{
    private User $user;

    private Wallet $cashWallet;

    private Wallet $bcaWallet;

    private Wallet $merchantWallet;

    private Wallet $externalWallet;

    private Wallet $debtWallet;

    private Wallet $receivableWallet;

    private Category $foodCategory;

    private Category $salaryCategory;

    private TransactionType $expenseType;

    private TransactionType $incomeType;

    private TransactionType $transferType;

    private TransactionType $debtType;

    private TransactionType $receivableType;

    protected function setUpRegressionData(): void
    {
        $this->user = User::factory()->create(['name' => 'Budi']);

        $this->user->wallets()->forceDelete();
        $this->user->categories()->forceDelete();

        $this->expenseType = TransactionType::create(['name' => 'Expense']);
        $this->incomeType = TransactionType::create(['name' => 'Income']);
        $this->transferType = TransactionType::create(['name' => 'Transfer']);
        $this->debtType = TransactionType::create(['name' => 'Debt']);
        $this->receivableType = TransactionType::create(['name' => 'Receivable']);

        $this->foodCategory = $this->user->categories()->create([
            'category_name' => 'Makan & Minum',
            'keyword' => 'makan, minum, kopi, bakso',
            'type_id' => $this->expenseType->id,
        ]);

        $this->user->categories()->create([
            'category_name' => 'Transportasi',
            'keyword' => 'bensin, angkot, grab, gojek',
            'type_id' => $this->expenseType->id,
        ]);

        $this->salaryCategory = $this->user->categories()->create([
            'category_name' => 'Gaji',
            'keyword' => 'gaji, salary, upah',
            'type_id' => $this->incomeType->id,
        ]);

        $this->user->categories()->create([
            'category_name' => 'Bonus',
            'keyword' => 'bonus, thr',
            'type_id' => $this->incomeType->id,
        ]);

        $this->user->categories()->create([
            'category_name' => 'Dapat Hutangan',
            'keyword' => 'dapat hutangan, ngutang, pinjam duit',
            'type_id' => $this->debtType->id,
            'system_key' => 'LOAN',
        ]);

        $this->user->categories()->create([
            'category_name' => 'Bayar Cicilan Hutang',
            'keyword' => 'bayar cicilan hutang, bayar hutang, lunasin hutang',
            'type_id' => $this->debtType->id,
            'system_key' => 'DEBT_PAYMENT',
        ]);

        $this->user->categories()->create([
            'category_name' => 'Ngasih Piutang',
            'keyword' => 'ngasih piutang, pinjamin, ngutangin',
            'type_id' => $this->receivableType->id,
            'system_key' => 'RECEIVABLE',
        ]);

        $this->user->categories()->create([
            'category_name' => 'Terima Bayar Piutang',
            'keyword' => 'terima bayar piutang, balikin piutang, bayar piutang',
            'type_id' => $this->receivableType->id,
            'system_key' => 'RECEIVABLE_PAYMENT',
        ]);

        $this->cashWallet = $this->user->wallets()->create([
            'name' => 'Dompet Cash',
            'keyword' => 'cash, tunai',
            'balance' => 500000,
            'group_type' => 'Liquid',
        ]);

        $this->bcaWallet = $this->user->wallets()->create([
            'name' => 'BCA',
            'keyword' => 'bca, transfer',
            'balance' => 1000000,
            'group_type' => 'Liquid',
        ]);

        $this->merchantWallet = $this->user->wallets()->create([
            'name' => 'Merchant System',
            'group_type' => 'System',
        ]);

        $this->externalWallet = $this->user->wallets()->create([
            'name' => 'External System',
            'group_type' => 'System',
        ]);

        $this->debtWallet = $this->user->wallets()->create([
            'name' => 'Hutang System',
            'group_type' => 'System',
        ]);

        $this->receivableWallet = $this->user->wallets()->create([
            'name' => 'Piutang System',
            'group_type' => 'System',
        ]);

        config([
            'bendaharaku.system_wallets.merchant' => 'Merchant System',
            'bendaharaku.system_wallets.external' => 'External System',
            'bendaharaku.system_wallets.debt' => 'Hutang System',
            'bendaharaku.system_wallets.receivable' => 'Piutang System',
        ]);
    }

    protected function makeDraft(array $overrides = []): TransactionDraft
    {
        $defaults = [
            'user_id' => $this->user->id,
            'ai_provider' => 'gemini',
            'ai_model' => 'gemini-1.5-flash',
            'draft_type' => 'single',
            'status' => 'pending',
            'ai_confidence' => 0.90,
            'original_text' => 'beli bakso 15rb cash',
            'payload' => [
                'amount' => 15000,
                'category_id' => $this->foodCategory->id,
                'category_name' => $this->foodCategory->category_name,
                'source_wallet_id' => $this->cashWallet->id,
                'source_wallet_name' => $this->cashWallet->name,
                'destination_wallet_id' => $this->merchantWallet->id,
                'destination_wallet_name' => $this->merchantWallet->name,
                'subject' => 'Budi',
                'notes' => 'beli bakso 15rb cash',
                'type_key' => 'expense',
                'needs_wallet' => false,
                'is_cleared' => false,
                'date' => now()->format('Y-m-d'),
            ],
        ];

        return TransactionDraft::create(array_replace_recursive($defaults, $overrides));
    }

    protected function mockAiParseResult(float $confidence = 0.95, ?ParsedTransaction $parsed = null): AIParseResult
    {
        if ($parsed === null) {
            $parsed = new ParsedTransaction(
                amount: 15000,
                transactionType: TransactionIntent::Expense,
                category: 'makan',
                sourceWallet: 'cash',
                isCleared: true,
            );
        }

        return new AIParseResult(true, $confidence, null, $parsed);
    }

    protected function assertBalanceEquals(Wallet $wallet, float $expected): void
    {
        $this->assertEquals($expected, (float) $wallet->fresh()->balance);
    }

    protected function assertTransactionLogged(array $attributes): void
    {
        $this->assertDatabaseHas('transaction_logs', $attributes);
    }

    protected function assertDraftSaved(array $attributes): void
    {
        $this->assertDatabaseHas('transaction_drafts', $attributes);
    }

    protected function assertAiUsageLogged(): void
    {
        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $this->user->id,
        ]);
    }
}
