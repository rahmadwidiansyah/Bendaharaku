<?php

namespace Tests\Feature;

use App\Actions\ProcessTransactionAction;
use App\Enums\TransactionSource;
use App\Models\LoanBalance;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Models\User;
use App\Services\Chat\DraftConfirmationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $walletId;

    private int $hutangId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
        // Isolasi guard loan dari guard overdraft wallet: izinkan saldo negatif
        // agar overpayment diuji terhadap LoanBalanceService, bukan applyTransaction.
        $this->user->update(['allow_negative_balance' => true]);
        // Pin wallet deterministik (Dompet Cash bawaan User::booted, saldo awal 0).
        $this->walletId = $this->user->wallets()->where('name', 'Dompet Cash')->value('id')
            ?? $this->user->wallets()->where('group_type', 'Liquid')->orderBy('id')->value('id');
        $this->hutangId = $this->user->wallets()->where('name', 'Hutang System')->value('id')
            ?? $this->user->wallets()->where('group_type', 'System')->orderBy('id')->value('id');
    }

    private function liquidWallet()
    {
        return $this->user->wallets()->findOrFail($this->walletId);
    }

    private function systemHutang()
    {
        return $this->user->wallets()->findOrFail($this->hutangId);
    }

    private function balance(string $subject, string $type): float
    {
        return (float) (LoanBalance::where('user_id', $this->user->id)
            ->where('subject', $subject)
            ->where('loan_type', $type)
            ->value('balance') ?? 0);
    }

    private function loanPayload(string $subject, int $amount, array $over = []): array
    {
        return array_merge([
            'date' => now()->format('Y-m-d'),
            'category_id' => null,
            'source_wallet_id' => $this->systemHutang()->id,
            'destination_wallet_id' => $this->liquidWallet()->id,
            'amount' => $amount,
            'transaction_type' => 'debt',
            'debt_sub_type' => 'income',
            'subject' => $subject,
        ], $over);
    }

    private function paymentPayload(string $subject, int $amount, array $over = []): array
    {
        return array_merge([
            'date' => now()->format('Y-m-d'),
            'category_id' => null,
            'source_wallet_id' => $this->liquidWallet()->id,
            'destination_wallet_id' => $this->systemHutang()->id,
            'amount' => $amount,
            'transaction_type' => 'debt',
            'debt_sub_type' => 'expense',
            'subject' => $subject,
        ], $over);
    }

    public function test_partial_payment_reduces_balance_and_wallet()
    {
        $before = (float) $this->liquidWallet()->balance;

        $this->actingAs($this->user)->post(route('transactions.store'), $this->loanPayload('BUDI', 100000));
        $this->actingAs($this->user)->post(route('transactions.store'), $this->paymentPayload('BUDI', 40000))
            ->assertRedirect(route('dashboard'));

        $this->assertEquals(60000, $this->balance('BUDI', 'debt'));
        $this->assertEquals($before + 100000 - 40000, (float) $this->liquidWallet()->fresh()->balance);
        $this->assertTrue(TransactionLog::latest('id')->firstOrFail()->is_cleared);
    }

    public function test_exact_settlement_removes_balance_row()
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->loanPayload('BUDI', 100000));
        $this->actingAs($this->user)->post(route('transactions.store'), $this->paymentPayload('BUDI', 100000));

        $this->assertDatabaseMissing('loan_balances', [
            'user_id' => $this->user->id, 'subject' => 'BUDI', 'loan_type' => 'debt',
        ]);
        $this->assertEquals(0, $this->balance('BUDI', 'debt'));
    }

    public function test_overpayment_rejected_with_full_rollback()
    {
        $walletBefore = (float) $this->liquidWallet()->balance;
        $this->actingAs($this->user)->post(route('transactions.store'), $this->loanPayload('BUDI', 100000));

        $response = $this->actingAs($this->user)->post(route('transactions.store'), $this->paymentPayload('BUDI', 150000));
        $response->assertSessionHas('error');

        $this->assertEquals(100000, $this->balance('BUDI', 'debt'));
        $this->assertEquals($walletBefore + 100000, (float) $this->liquidWallet()->fresh()->balance);
        $this->assertDatabaseMissing('transaction_logs', [
            'user_id' => $this->user->id, 'subject' => 'BUDI', 'amount' => 150000,
        ]);
    }

    public function test_double_full_payment_second_rejected()
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->loanPayload('BUDI', 100000));
        $this->actingAs($this->user)->post(route('transactions.store'), $this->paymentPayload('BUDI', 100000))
            ->assertRedirect(route('dashboard'));

        // Submit identik kedua: tidak ada idempotency-key backend, tetapi
        // overpayment guard menolak karena sisa sudah 0.
        $this->actingAs($this->user)->post(route('transactions.store'), $this->paymentPayload('BUDI', 100000))
            ->assertSessionHas('error');

        // Tepat 2 baris: 1 LOAN + 1 pembayaran. Tidak ada baris ketiga.
        $this->assertEquals(2, TransactionLog::where('user_id', $this->user->id)->where('subject', 'BUDI')->count());
    }

    public function test_delete_payment_restores_balance()
    {
        $action = app(ProcessTransactionAction::class);
        $walletBefore = (float) $this->liquidWallet()->balance;

        $this->actingAs($this->user)->post(route('transactions.store'), $this->loanPayload('BUDI', 100000));
        $this->actingAs($this->user)->post(route('transactions.store'), $this->paymentPayload('BUDI', 30000));
        $payment = TransactionLog::where('user_id', $this->user->id)->where('subject', 'BUDI')->latest('id')->firstOrFail();

        $action->delete($payment);

        $this->assertEquals(100000, $this->balance('BUDI', 'debt'));
        $this->assertEquals($walletBefore + 100000, (float) $this->liquidWallet()->fresh()->balance);
        $this->assertSoftDeleted('transaction_logs', ['id' => $payment->id]);
    }

    public function test_delete_loan_with_remaining_payments_rejected()
    {
        $walletBefore = (float) $this->liquidWallet()->balance;

        $this->actingAs($this->user)->post(route('transactions.store'), $this->loanPayload('BUDI', 100000));
        $this->actingAs($this->user)->post(route('transactions.store'), $this->paymentPayload('BUDI', 30000));
        $loan = TransactionLog::where('user_id', $this->user->id)->where('subject', 'BUDI')->oldest('id')->firstOrFail();

        $failed = false;
        try {
            app(ProcessTransactionAction::class)->delete($loan);
        } catch (\Throwable) {
            $failed = true;
        }

        $this->assertTrue($failed, 'Menghapus LOAN dengan sisa pembayaran harus ditolak.');
        $this->assertEquals(70000, $this->balance('BUDI', 'debt'));
        $this->assertEquals($walletBefore + 70000, (float) $this->liquidWallet()->fresh()->balance);
        $this->assertDatabaseHas('transaction_logs', ['id' => $loan->id, 'deleted_at' => null]);
    }

    public function test_edit_amount_updates_balance_and_wallets()
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->loanPayload('BUDI', 100000));
        $this->actingAs($this->user)->post(route('transactions.store'), $this->paymentPayload('BUDI', 30000));
        $loan = TransactionLog::where('user_id', $this->user->id)->where('subject', 'BUDI')->oldest('id')->firstOrFail();
        $walletBefore = (float) $this->liquidWallet()->fresh()->balance;

        $this->actingAs($this->user)->put(route('transactions.update', $loan->id), $this->loanPayload('BUDI', 150000))
            ->assertRedirect(route('dashboard'));

        $this->assertEquals(120000, $this->balance('BUDI', 'debt'));
        $this->assertEquals($walletBefore + 50000, (float) $this->liquidWallet()->fresh()->balance);
    }

    public function test_edit_subject_moves_balance()
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->loanPayload('BUDI', 100000));
        $loan = TransactionLog::where('user_id', $this->user->id)->where('subject', 'BUDI')->firstOrFail();

        $this->actingAs($this->user)->put(route('transactions.update', $loan->id), $this->loanPayload('ANI', 100000))
            ->assertRedirect(route('dashboard'));

        $this->assertEquals(0, $this->balance('BUDI', 'debt'));
        $this->assertEquals(100000, $this->balance('ANI', 'debt'));
    }

    public function test_change_category_loan_to_non_loan_clears_loan_balance()
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->loanPayload('BUDI', 100000));
        $loan = TransactionLog::where('user_id', $this->user->id)->where('subject', 'BUDI')->firstOrFail();
        $expenseCategory = $this->user->categories()->whereHas('type', fn ($q) => $q->where('name', 'Expense'))->firstOrFail();

        $this->actingAs($this->user)->put(route('transactions.update', $loan->id), [
            'date' => now()->format('Y-m-d'),
            'category_id' => $expenseCategory->id,
            'source_wallet_id' => $this->liquidWallet()->id,
            'destination_wallet_id' => $this->systemHutang()->id,
            'amount' => 100000,
            'transaction_type' => 'expense',
            'subject' => 'BUDI',
        ])->assertRedirect(route('dashboard'));

        $this->assertEquals(0, $this->balance('BUDI', 'debt'));
        $this->assertDatabaseMissing('loan_balances', [
            'user_id' => $this->user->id, 'subject' => 'BUDI', 'loan_type' => 'debt',
        ]);
    }

    public function test_confirm_uncleared_loan_updates_loan_balance()
    {
        $action = app(ProcessTransactionAction::class);

        $log = $action->create(
            data: array_merge($this->loanPayload('BUDI', 100000), ['is_cleared' => false]),
            userId: $this->user->id,
            sourcePrefix: 'TRX',
            source: TransactionSource::SYSTEM,
        );

        $this->assertFalse($log->is_cleared);
        $this->assertEquals(0, $this->balance('BUDI', 'debt'));

        $action->confirm($log->fresh());

        $this->assertTrue($log->fresh()->is_cleared);
        $this->assertEquals(100000, $this->balance('BUDI', 'debt'));
    }

    public function test_confirm_uncleared_payment_exceeding_balance_rejected()
    {
        $action = app(ProcessTransactionAction::class);

        $this->actingAs($this->user)->post(route('transactions.store'), $this->loanPayload('BUDI', 100000));
        $walletBefore = (float) $this->liquidWallet()->fresh()->balance;

        $draft = $action->create(
            data: array_merge($this->paymentPayload('BUDI', 150000), ['is_cleared' => false]),
            userId: $this->user->id,
            sourcePrefix: 'TRX',
            source: TransactionSource::SYSTEM,
        );

        $failed = false;
        try {
            $action->confirm($draft->fresh());
        } catch (\Throwable) {
            $failed = true;
        }

        $this->assertTrue($failed, 'Konfirmasi pembayaran melebihi sisa harus ditolak.');
        $this->assertEquals(100000, $this->balance('BUDI', 'debt'));
        $this->assertEquals($walletBefore, (float) $this->liquidWallet()->fresh()->balance);
    }

    public function test_draft_confirm_loan_updates_balance()
    {
        $loanCategory = $this->user->categories()->where('system_key', 'LOAN')->firstOrFail();

        $draft = TransactionDraft::create([
            'user_id' => $this->user->id,
            'ai_provider' => 'test',
            'ai_model' => 'test',
            'draft_type' => 'single',
            'status' => 'pending',
            'ai_confidence' => 0.9,
            'original_text' => 'pinjam 100rb budi',
            'expires_at' => now()->addHours(24),
            'payload' => [
                'date' => now()->format('Y-m-d'),
                'category_id' => $loanCategory->id,
                'source_wallet_id' => $this->systemHutang()->id,
                'destination_wallet_id' => $this->liquidWallet()->id,
                'amount' => 100000,
                'subject' => 'BUDI',
                'notes' => 'pinjam 100rb budi',
            ],
        ]);

        app(DraftConfirmationService::class)->confirm($draft->fresh(), $this->user);

        $this->assertEquals(100000, $this->balance('BUDI', 'debt'));
        $this->assertEquals('confirmed', $draft->fresh()->status);
    }

    public function test_cross_user_isolation()
    {
        $other = User::factory()->create();

        $this->actingAs($this->user)->post(route('transactions.store'), $this->loanPayload('BUDI', 100000));
        $loan = TransactionLog::where('user_id', $this->user->id)->where('subject', 'BUDI')->firstOrFail();
        $otherWallet = $other->wallets()->where('group_type', 'Liquid')->firstOrFail();

        // User lain tidak boleh melihat/mengubah/menghapus.
        $this->actingAs($other)->get(route('transactions.edit', $loan->id))->assertNotFound();
        $this->actingAs($other)->put(route('transactions.update', $loan->id), $this->loanPayload('BUDI', 1, [
            'source_wallet_id' => $otherWallet->id,
            'destination_wallet_id' => $otherWallet->id,
        ]))->assertNotFound();
        $this->actingAs($other)->delete(route('transactions.destroy', $loan->id))->assertNotFound();

        $this->assertEquals(100000, $this->balance('BUDI', 'debt'));
        $this->assertEquals(0, (float) (LoanBalance::where('user_id', $other->id)->sum('balance') ?? 0));
    }
}
