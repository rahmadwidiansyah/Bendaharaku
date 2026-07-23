<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\TransactionIntent;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AI\LocalRuleEngine;
use App\Services\Category\CategoryResolutionService;
use App\Services\Wallet\WalletResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalRuleEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private LocalRuleEngine $ruleEngine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ruleEngine = new LocalRuleEngine(
            new CategoryResolutionService,
            new WalletResolutionService,
        );

        // 1. Create Transaction Types
        $incomeType = TransactionType::create(['name' => 'Income']);
        $expenseType = TransactionType::create(['name' => 'Expense']);
        $transferType = TransactionType::create(['name' => 'Transfer']);
        $debtType = TransactionType::create(['name' => 'Debt']);
        $receivableType = TransactionType::create(['name' => 'Receivable']);

        // 2. Create User
        $this->user = User::factory()->create(['name' => 'Budi']);

        // Clear default boot categories/wallets to avoid duplication
        $this->user->wallets()->forceDelete();
        $this->user->categories()->forceDelete();

        // 3. Create Wallets
        $this->user->wallets()->createMany([
            ['name' => 'Bank BCA', 'keyword' => 'bca', 'group_type' => 'Liquid', 'balance' => 1000000],
            ['name' => 'Dompet Cash', 'keyword' => 'cash, tunai', 'group_type' => 'Liquid', 'balance' => 500000],
            ['name' => 'OVO', 'keyword' => 'ovo', 'group_type' => 'Liquid', 'balance' => 200000],
            ['name' => 'System Hutang', 'group_type' => 'System'],
            ['name' => 'System Piutang', 'group_type' => 'System'],
        ]);

        // 4. Create Categories
        $this->user->categories()->createMany([
            [
                'category_name' => 'Pindah Saldo',
                'type_id' => $transferType->id,
                'icon' => '🔄',
                'keyword' => 'transfer, pindah uang, pindahkan uang, pindahkan saldo, kirim saldo, kirim uang, pindah semua saldo, transfer semua saldo, mutasi, pindah saldo',
                'system_key' => 'TRANSFER',
            ],
            [
                'category_name' => 'Dapat Hutangan',
                'type_id' => $debtType->id,
                'icon' => '📥',
                'keyword' => 'dapat hutangan, ngutang, pinjam duit, ditalangin, kasbon, pinjol, minjem uang, pinjam uang, dapet pinjeman, hutang, utang, pinjam, minjam, pinjem, berhutang, berutang',
                'system_key' => 'LOAN',
            ],
            [
                'category_name' => 'Bayar Cicilan Hutang',
                'type_id' => $debtType->id,
                'icon' => '💸',
                'keyword' => 'bayar utang, bayar hutang, lunasin, nyicil, cicilan, balikin duit, balikin uang, ganti duit, nutup utang, bayar kasbon, bayar pinjol, lunasi hutang, lunasin utang, balikin pinjaman, melunasi pinjaman, kembalikan hutang',
                'system_key' => 'DEBT_PAYMENT',
            ],
            [
                'category_name' => 'Ngasih Piutang',
                'type_id' => $receivableType->id,
                'icon' => '📤',
                'keyword' => 'ngasih piutang, minjemin, ngutangin, dipinjem, dipinjam, nalangin, kasih utang, pinjemin, pinjamin, ngasih pinjaman, kasih pinjam, meminjamkan, memberi pinjaman',
                'system_key' => 'RECEIVABLE',
            ],
            [
                'category_name' => 'Terima Bayar Piutang',
                'type_id' => $receivableType->id,
                'icon' => '🤑',
                'keyword' => 'terima bayar piutang, dibayar, utang dibayar, utang lunas, ditagih, nagih utang, teman balikin, uang kembali, pelunasan teman, piutang dibayar, dibayar hutang, dibayar utang, balikin uang, balikin pinjaman, mengembalikan pinjaman, mengembalikan uang, kembalikan uang, kupinjamkan, menerima pembayaran hutang, menerima pembayaran piutang',
                'system_key' => 'RECEIVABLE_PAYMENT',
            ],
        ]);
    }

    /** @test */
    public function test_it_parses_debt_and_receivable_scenarios(): void
    {
        // 1. "Iqbal bayar hutang 20 ribu tunai"
        // Intent: Debt (DEBT_PAYMENT), Amount: 20000, Wallet: Dompet Cash, Subject: Iqbal
        $result = $this->ruleEngine->parse($this->user, 'Iqbal bayar hutang 20 ribu tunai');
        $this->assertNotNull($result);
        $this->assertTrue($result->success);
        $this->assertEquals(20000, $result->transaction->amount);
        $this->assertEquals(TransactionIntent::Debt, $result->transaction->transactionType);
        $this->assertEquals('Bayar Cicilan Hutang', $result->transaction->category);
        $this->assertEquals('Dompet Cash', $result->transaction->sourceWallet);
        $this->assertEquals('Iqbal', $result->transaction->subject);

        // 2. "Iqbal balikin pinjaman 20 ribu"
        // Intent: Debt (DEBT_PAYMENT), Amount: 20000, Subject: Iqbal
        $result = $this->ruleEngine->parse($this->user, 'Iqbal balikin pinjaman 20 ribu');
        $this->assertNotNull($result);
        $this->assertTrue($result->success);
        $this->assertEquals(20000, $result->transaction->amount);
        $this->assertEquals(TransactionIntent::Debt, $result->transaction->transactionType);
        $this->assertEquals('Bayar Cicilan Hutang', $result->transaction->category);
        $this->assertEquals('Iqbal', $result->transaction->subject);

        // 3. "Iqbal mengembalikan uang yang kupinjamkan 20 ribu"
        $result = $this->ruleEngine->parse($this->user, 'Iqbal mengembalikan uang yang kupinjamkan 20 ribu');
        $this->assertNotNull($result);
        $this->assertTrue($result->success);
        $this->assertEquals(20000, $result->transaction->amount);
        $this->assertEquals(TransactionIntent::Receivable, $result->transaction->transactionType);
        $this->assertEquals('Terima Bayar Piutang', $result->transaction->category);
        $this->assertEquals('Iqbal', $result->transaction->subject);

        // 4. "Iqbal lunasin hutangnya" -> Fallback to AI because no amount (rule engine returns null)
        $result = $this->ruleEngine->parse($this->user, 'Iqbal lunasin hutangnya');
        $this->assertNull($result);

        // Test with amount: "Iqbal lunasin hutangnya 20k"
        $result = $this->ruleEngine->parse($this->user, 'Iqbal lunasin hutangnya 20k');
        $this->assertNotNull($result);
        $this->assertEquals(20000, $result->transaction->amount);
        $this->assertEquals(TransactionIntent::Debt, $result->transaction->transactionType);
        $this->assertEquals('Bayar Cicilan Hutang', $result->transaction->category);
        $this->assertEquals('Iqbal', $result->transaction->subject);

        // 5. "Pinjamin Andi 100 ribu"
        // Intent: Receivable (RECEIVABLE), Amount: 100000, Subject: Andi
        $result = $this->ruleEngine->parse($this->user, 'Pinjamin Andi 100 ribu');
        $this->assertNotNull($result);
        $this->assertTrue($result->success);
        $this->assertEquals(100000, $result->transaction->amount);
        $this->assertEquals(TransactionIntent::Receivable, $result->transaction->transactionType);
        $this->assertEquals('Ngasih Piutang', $result->transaction->category);
        $this->assertEquals('Andi', $result->transaction->subject);

        // 6. "Kasih pinjam Budi 50 ribu"
        // Intent: Receivable (RECEIVABLE), Amount: 50000, Subject: Budi
        $result = $this->ruleEngine->parse($this->user, 'Kasih pinjam Budi 50 ribu');
        $this->assertNotNull($result);
        $this->assertTrue($result->success);
        $this->assertEquals(50000, $result->transaction->amount);
        $this->assertEquals(TransactionIntent::Receivable, $result->transaction->transactionType);
        $this->assertEquals('Ngasih Piutang', $result->transaction->category);
        $this->assertEquals('Budi', $result->transaction->subject);

        // 7. "Budi balikin uang 50 ribu"
        // Intent: Receivable (RECEIVABLE_PAYMENT), Amount: 50000, Subject: Budi
        $result = $this->ruleEngine->parse($this->user, 'Budi balikin uang 50 ribu');
        $this->assertNotNull($result);
        $this->assertTrue($result->success);
        $this->assertEquals(50000, $result->transaction->amount);
        $this->assertEquals(TransactionIntent::Receivable, $result->transaction->transactionType);
        $this->assertEquals('Terima Bayar Piutang', $result->transaction->category);
        $this->assertEquals('Budi', $result->transaction->subject);
    }

    /** @test */
    public function test_it_parses_transfer_scenarios(): void
    {
        // 1. "Transfer semua saldo BCA ke Cash"
        // Intent: Transfer, useAllBalance: true, Source: Bank BCA, Destination: Dompet Cash
        $result = $this->ruleEngine->parse($this->user, 'Transfer semua saldo BCA ke Cash');
        $this->assertNotNull($result);
        $this->assertTrue($result->success);
        $this->assertTrue($result->transaction->useAllBalance);
        $this->assertEquals(TransactionIntent::Transfer, $result->transaction->transactionType);
        $this->assertEquals('Pindah Saldo', $result->transaction->category);
        $this->assertEquals('Bank BCA', $result->transaction->sourceWallet);
        $this->assertEquals('Dompet Cash', $result->transaction->destinationWallet);

        // 2. "Pindahkan uang ke OVO"
        // Intent: Transfer, Amount: none (so fallback to AI, wait - since no amount or all balance, returns null)
        $result = $this->ruleEngine->parse($this->user, 'Pindahkan uang ke OVO');
        $this->assertNull($result);

        // With amount: "Pindahkan uang 50k ke OVO"
        $result = $this->ruleEngine->parse($this->user, 'Pindahkan uang 50k ke OVO');
        $this->assertNotNull($result);
        $this->assertEquals(50000, $result->transaction->amount);
        $this->assertEquals(TransactionIntent::Transfer, $result->transaction->transactionType);
        $this->assertEquals('OVO', $result->transaction->destinationWallet);
    }
}
