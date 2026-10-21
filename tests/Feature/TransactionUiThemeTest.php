<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionUiThemeTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED_PROPS = [
        'wallets', 'systemWallets', 'categories', 'debtSubjects', 'receivableSubjects',
    ];

    private function makeUser(string $theme): User
    {
        return User::factory()->create(['ui_theme' => $theme]);
    }

    private function makeTransaction(User $user): TransactionLog
    {
        $type = TransactionType::create([
            'user_id' => $user->id,
            'name' => 'Expense',
            'keyword' => 'expense',
        ]);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'name' => 'Dompet Tunai',
            'balance' => 100000,
            'group_type' => 'Liquid',
            'is_active' => true,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'type_id' => $type->id,
            'category_name' => 'Makan & Minum',
            'icon' => 'utensils',
            'is_active' => true,
        ]);

        return TransactionLog::create([
            'reference_number' => 'TXNTHEME-'.strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'type_id' => $type->id,
            'category_id' => $category->id,
            'source_wallet_id' => $wallet->id,
            'amount' => 25000,
            'balance_before' => 100000,
            'balance_after' => 75000,
            'subject' => '-',
            'notes' => null,
            'is_cleared' => true,
        ]);
    }

    public function test_default_user_gets_default_create_page()
    {
        $user = $this->makeUser('default');

        $this->actingAs($user)
            ->get('/transactions/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Transactions/Create')
                ->where('auth.user.ui_theme', 'default')
                ->hasAll(self::EXPECTED_PROPS));
    }

    public function test_m3_user_gets_material_create_page()
    {
        $user = $this->makeUser('m3');

        $this->actingAs($user)
            ->get('/transactions/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Transactions/Create')
                ->where('auth.user.ui_theme', 'm3')
                ->hasAll(self::EXPECTED_PROPS));
    }

    public function test_default_user_gets_default_edit_page()
    {
        $user = $this->makeUser('default');
        $transaction = $this->makeTransaction($user);

        $this->actingAs($user)
            ->get("/transactions/{$transaction->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Transactions/Edit')
                ->where('auth.user.ui_theme', 'default')
                ->where('transaction.id', $transaction->id)
                ->hasAll(self::EXPECTED_PROPS));
    }

    public function test_m3_user_gets_material_edit_page()
    {
        $user = $this->makeUser('m3');
        $transaction = $this->makeTransaction($user);

        $this->actingAs($user)
            ->get("/transactions/{$transaction->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Transactions/Edit')
                ->where('auth.user.ui_theme', 'm3')
                ->where('transaction.id', $transaction->id)
                ->hasAll(self::EXPECTED_PROPS));
    }

    public function test_default_user_gets_default_index_page()
    {
        $user = $this->makeUser('default');
        $this->makeTransaction($user);

        $this->actingAs($user)
            ->get('/transactions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Transactions/Index')
                ->where('auth.user.ui_theme', 'default')
                ->hasAll(['transactions', 'dailyTotals', 'calendarMarkers', 'startDate', 'endDate', 'filters', 'limit']));
    }

    public function test_m3_user_gets_material_index_page()
    {
        $user = $this->makeUser('m3');
        $this->makeTransaction($user);

        $this->actingAs($user)
            ->get('/transactions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Transactions/Index')
                ->where('auth.user.ui_theme', 'm3')
                ->hasAll(['transactions', 'dailyTotals', 'calendarMarkers', 'startDate', 'endDate', 'filters', 'limit']));
    }
}
