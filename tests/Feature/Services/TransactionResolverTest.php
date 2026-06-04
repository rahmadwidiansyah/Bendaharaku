<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\TransactionType;
use App\DTO\ParsedTransaction;
use App\Enums\TransactionIntent;
use App\Services\AI\TransactionResolver;
use App\Exceptions\CategoryNotFoundException;
use App\Exceptions\WalletNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionResolverTest extends TestCase
{
    use RefreshDatabase;

    private TransactionResolver $resolver;
    private User $user;
    private TransactionType $expenseType;
    private TransactionType $incomeType;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->resolver = new TransactionResolver();

        $this->incomeType = TransactionType::create(['name' => 'Income']);
        $this->expenseType = TransactionType::create(['name' => 'Expense']);
        TransactionType::create(['name' => 'Transfer']);
        TransactionType::create(['name' => 'Debt']);
        TransactionType::create(['name' => 'Receivable']);

        // Instansiasi user
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_throws_exception_when_configured_system_wallet_does_not_exist(): void
    {
        $this->user->categories()->create([
            'category_name' => 'Makan & Minum',
            'keyword'       => 'makan',
            'type_id'       => $this->expenseType->id,
        ]);

        $this->user->wallets()->create([
            'name'       => 'Dompet Cash',
            'keyword'    => 'cash',
            'group_type' => 'Liquid',
        ]);

        // Mengatur config ke nama dompet yang TIDAK ADA di database
        config(['bendaharaku.system_wallets.merchant' => 'Merchant Ghaib System']);

        $parsed = new ParsedTransaction(
            amount: 25000,
            transactionType: TransactionIntent::Expense,
            category: 'makan',
            sourceWallet: 'cash'
        );

        // Harus fail-fast dan melempar WalletNotFoundException, bukan mencari fallback
        $this->expectException(WalletNotFoundException::class);
        $this->expectExceptionMessage("Dompet sistem untuk arus kas 'Merchant Ghaib System' tidak terdeteksi");

        $this->resolver->resolve($this->user, $parsed);
    }

    /** @test */
    public function it_resolves_expense_to_configured_merchant_system_wallet(): void
    {
        $category = $this->user->categories()->create([
            'category_name' => 'Makan & Minum',
            'keyword'       => 'makan',
            'type_id'       => $this->expenseType->id,
        ]);

        $sourceWallet = $this->user->wallets()->create([
            'name'       => 'Dompet Cash',
            'keyword'    => 'cash',
            'group_type' => 'Liquid',
        ]);

        $merchantWallet = $this->user->wallets()->create([
            'name'       => 'Toko Merchant Internal',
            'group_type' => 'System',
        ]);

        // Mengatur SSOT Config
        config(['bendaharaku.system_wallets.merchant' => 'Toko Merchant Internal']);

        $parsed = new ParsedTransaction(
            amount: 50000,
            transactionType: TransactionIntent::Expense,
            category: 'makan',
            sourceWallet: 'cash'
        );

        $resolved = $this->resolver->resolve($this->user, $parsed);

        $this->assertEquals($category->id, $resolved->categoryId);
        $this->assertEquals($sourceWallet->id, $resolved->sourceWalletId);
        $this->assertEquals($merchantWallet->id, $resolved->destinationWalletId);
    }

    /** @test */
    public function it_resolves_income_to_configured_external_system_wallet(): void
    {
        $category = $this->user->categories()->create([
            'category_name' => 'Gajian Bulanan',
            'keyword'       => 'gaji, bonus',
            'type_id'       => $this->incomeType->id,
        ]);

        $destWallet = $this->user->wallets()->create([
            'name'       => 'Bank BCA',
            'keyword'    => 'bca',
            'group_type' => 'Liquid',
        ]);

        $externalWallet = $this->user->wallets()->create([
            'name'       => 'Pihak Luar Eksternal',
            'group_type' => 'System',
        ]);

        // Mengatur SSOT Config
        config(['bendaharaku.system_wallets.external' => 'Pihak Luar Eksternal']);

        $parsed = new ParsedTransaction(
            amount: 5000000,
            transactionType: TransactionIntent::Income,
            category: 'gaji',
            sourceWallet: 'bca' // AI biasanya mendeteksi dompet tujuan sebagai input teks "masuk ke BCA"
        );

        $resolved = $this->resolver->resolve($this->user, $parsed);

        $this->assertEquals($category->id, $resolved->categoryId);
        // Income dibalik secara akuntansi: Source = External, Dest = User Wallet
        $this->assertEquals($externalWallet->id, $resolved->sourceWalletId);
        $this->assertEquals($destWallet->id, $resolved->destinationWalletId);
    }

    /** @test */
    public function it_resolves_category_using_pipe_or_semicolon_regex_delimiters(): void
    {
        $category = $this->user->categories()->create([
            'category_name' => 'Cemilan',
            'keyword'       => 'snack | jajan ; kerupuk, roti',
            'type_id'       => $this->expenseType->id
        ]);

        $this->user->wallets()->create([
            'name'       => 'Dompet Tunai',
            'keyword'    => 'cash',
            'group_type' => 'Liquid'
        ]);

        // Pastikan config default ada agar tidak trigger Exception pada expense
        $this->user->wallets()->create([
            'name'       => 'Merchant System',
            'group_type' => 'System'
        ]);
        config(['bendaharaku.system_wallets.merchant' => 'Merchant System']);

        $parsed = new ParsedTransaction(
            amount: 15000,
            transactionType: TransactionIntent::Expense,
            category: 'jajan', 
            sourceWallet: 'cash'
        );

        $resolved = $this->resolver->resolve($this->user, $parsed);

        $this->assertEquals($category->id, $resolved->categoryId);
    }
}