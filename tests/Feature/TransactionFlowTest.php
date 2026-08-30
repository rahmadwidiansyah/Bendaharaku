<?php

namespace Tests\Feature;

use App\Models\TransactionLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed standard DatabaseSeeder to get all standard system wallets and categories
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    /**
     * Test flow Dapat Hutang (Loan)
     */
    public function test_can_create_debt_loan_transaction()
    {
        $liquidWallet = $this->user->wallets()->where('group_type', 'Liquid')->firstOrFail();
        $systemHutang = $this->user->wallets()->where('name', 'Hutang System')->firstOrFail();

        $payload = [
            'date' => now()->format('Y-m-d'),
            'category_id' => null, // nullable for debt
            'source_wallet_id' => $systemHutang->id,
            'destination_wallet_id' => $liquidWallet->id,
            'amount' => 150000,
            'transaction_type' => 'debt',
            'debt_sub_type' => 'income', // sub_type for LOAN (Dapat Hutang)
            'subject' => 'BUDI',
            'notes' => 'Pinjam uang ke Budi',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), $payload);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('transaction_logs', [
            'user_id' => $this->user->id,
            'amount' => 150000,
            'subject' => 'BUDI',
            'source_wallet_id' => $systemHutang->id,
            'destination_wallet_id' => $liquidWallet->id,
        ]);

        $log = TransactionLog::latest('id')->firstOrFail();
        $this->assertEquals('LOAN', $log->category->system_key);
    }

    /**
     * Test flow Bayar Hutang (Debt Payment)
     */
    public function test_can_create_debt_payment_transaction()
    {
        $liquidWallet = $this->user->wallets()->where('group_type', 'Liquid')->firstOrFail();
        $systemHutang = $this->user->wallets()->where('name', 'Hutang System')->firstOrFail();

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'date' => now()->format('Y-m-d'),
            'category_id' => null,
            'source_wallet_id' => $systemHutang->id,
            'destination_wallet_id' => $liquidWallet->id,
            'amount' => 100000,
            'transaction_type' => 'debt',
            'debt_sub_type' => 'income',
            'subject' => 'BUDI',
        ]);

        $payload = [
            'date' => now()->format('Y-m-d'),
            'category_id' => null,
            'source_wallet_id' => $liquidWallet->id,
            'destination_wallet_id' => $systemHutang->id,
            'amount' => 50000,
            'transaction_type' => 'debt',
            'debt_sub_type' => 'expense', // sub_type for DEBT_PAYMENT
            'subject' => 'BUDI',
            'notes' => 'Bayar cicilan hutang ke Budi',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), $payload);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('transaction_logs', [
            'user_id' => $this->user->id,
            'amount' => 50000,
            'subject' => 'BUDI',
            'source_wallet_id' => $liquidWallet->id,
            'destination_wallet_id' => $systemHutang->id,
        ]);

        $log = TransactionLog::latest('id')->firstOrFail();
        $this->assertEquals('DEBT_PAYMENT', $log->category->system_key);
    }

    /**
     * Test flow Beri Piutang (Receivable)
     */
    public function test_can_create_receivable_give_transaction()
    {
        $liquidWallet = $this->user->wallets()->where('group_type', 'Liquid')->firstOrFail();
        $systemPiutang = $this->user->wallets()->where('name', 'Piutang System')->firstOrFail();

        $payload = [
            'date' => now()->format('Y-m-d'),
            'category_id' => null,
            'source_wallet_id' => $liquidWallet->id,
            'destination_wallet_id' => $systemPiutang->id,
            'amount' => 200000,
            'transaction_type' => 'receivable',
            'debt_sub_type' => 'expense', // sub_type for RECEIVABLE
            'subject' => 'ANI',
            'notes' => 'Pinjamkan uang ke Ani',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), $payload);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('transaction_logs', [
            'user_id' => $this->user->id,
            'amount' => 200000,
            'subject' => 'ANI',
            'source_wallet_id' => $liquidWallet->id,
            'destination_wallet_id' => $systemPiutang->id,
        ]);

        $log = TransactionLog::latest('id')->firstOrFail();
        $this->assertEquals('RECEIVABLE', $log->category->system_key);
    }

    /**
     * Test flow Terima Piutang (Receivable Payment)
     */
    public function test_can_create_receivable_payment_transaction()
    {
        $liquidWallet = $this->user->wallets()->where('group_type', 'Liquid')->firstOrFail();
        $systemPiutang = $this->user->wallets()->where('name', 'Piutang System')->firstOrFail();

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'date' => now()->format('Y-m-d'),
            'category_id' => null,
            'source_wallet_id' => $liquidWallet->id,
            'destination_wallet_id' => $systemPiutang->id,
            'amount' => 200000,
            'transaction_type' => 'receivable',
            'debt_sub_type' => 'expense',
            'subject' => 'ANI',
        ]);

        $payload = [
            'date' => now()->format('Y-m-d'),
            'category_id' => null,
            'source_wallet_id' => $systemPiutang->id,
            'destination_wallet_id' => $liquidWallet->id,
            'amount' => 100000,
            'transaction_type' => 'receivable',
            'debt_sub_type' => 'income', // sub_type for RECEIVABLE_PAYMENT
            'subject' => 'ANI',
            'notes' => 'Terima cicilan piutang dari Ani',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), $payload);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('transaction_logs', [
            'user_id' => $this->user->id,
            'amount' => 100000,
            'subject' => 'ANI',
            'source_wallet_id' => $systemPiutang->id,
            'destination_wallet_id' => $liquidWallet->id,
        ]);

        $log = TransactionLog::latest('id')->firstOrFail();
        $this->assertEquals('RECEIVABLE_PAYMENT', $log->category->system_key);
    }

    /**
     * Test flow Transfer Saldo
     */
    public function test_can_create_transfer_transaction()
    {
        $wallets = $this->user->wallets()->where('group_type', 'Liquid')->take(2)->get();
        $this->assertCount(2, $wallets);
        $w1 = $wallets[0];
        $w2 = $wallets[1];

        $payload = [
            'date' => now()->format('Y-m-d'),
            'category_id' => null,
            'source_wallet_id' => $w1->id,
            'destination_wallet_id' => $w2->id,
            'amount' => 300000,
            'transaction_type' => 'transfer',
            'subject' => '-',
            'notes' => 'Pindah dana antar dompet',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), $payload);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('transaction_logs', [
            'user_id' => $this->user->id,
            'amount' => 300000,
            'source_wallet_id' => $w1->id,
            'destination_wallet_id' => $w2->id,
        ]);

        $log = TransactionLog::latest('id')->firstOrFail();
        $this->assertEquals('TRANSFER', $log->category->system_key);
    }

    /**
     * Test update flow untuk transaction
     */
    public function test_can_update_transaction_flow()
    {
        $liquidWallet = $this->user->wallets()->where('group_type', 'Liquid')->firstOrFail();
        $systemHutang = $this->user->wallets()->where('name', 'Hutang System')->firstOrFail();
        $loanCategory = $this->user->categories()->where('system_key', 'LOAN')->firstOrFail();

        // Create transaction first
        $transaction = TransactionLog::create([
            'reference_number' => 'TRX-'.Str::ulid(),
            'user_id' => $this->user->id,
            'date' => now()->format('Y-m-d'),
            'type_id' => $loanCategory->type_id,
            'category_id' => $loanCategory->id,
            'source_wallet_id' => $systemHutang->id,
            'destination_wallet_id' => $liquidWallet->id,
            'amount' => 150000,
            'balance_before' => $liquidWallet->balance,
            'balance_after' => $liquidWallet->balance + 150000,
            'subject' => 'BUDI',
            'notes' => 'Hutang awal',
            'is_cleared' => true,
        ]);

        $payload = [
            'date' => now()->format('Y-m-d'),
            'category_id' => null, // test auto-resolve during edit/update
            'source_wallet_id' => $systemHutang->id,
            'destination_wallet_id' => $liquidWallet->id,
            'amount' => 75000,
            'transaction_type' => 'debt',
            'debt_sub_type' => 'income',
            'subject' => 'BUDI',
            'notes' => 'Hutang terupdate',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('transactions.update', $transaction->id), $payload);

        $response->assertRedirect(route('dashboard'));

        $transaction->refresh();
        $this->assertEquals(75000, $transaction->amount);
        $this->assertEquals('BUDI', $transaction->subject);
        $this->assertEquals('LOAN', $transaction->category->system_key);
        $this->assertEquals($systemHutang->id, $transaction->source_wallet_id);
        $this->assertEquals($liquidWallet->id, $transaction->destination_wallet_id);
    }
}
