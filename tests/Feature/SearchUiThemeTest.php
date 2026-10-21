<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchUiThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_user_gets_default_search_page()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get('/search?q=kopi')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Search/Index')
                ->where('query', 'kopi')
                ->where('auth.user.ui_theme', 'default'));
    }

    public function test_m3_user_gets_material_search_page()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get('/search?q=kopi')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Search/Index')
                ->where('query', 'kopi')
                ->where('auth.user.ui_theme', 'm3'));
    }

    public function test_search_returns_wallet_category_and_transaction_results()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);
        $transaction = $this->makeTransaction($user);

        $response = $this->actingAs($user)
            ->getJson(route('search.global', ['q' => 'Kopi']))
            ->assertOk();

        $results = collect($response->json('results'));
        $types = $results->pluck('type');

        $this->assertTrue($types->contains('Wallet'));
        $this->assertTrue($types->contains('Kategori'));
        $this->assertTrue($types->contains('Transaksi'));

        $wallet = $results->firstWhere('type', 'Wallet');
        $this->assertSame('wallet-'.$transaction->source_wallet_id, $wallet['id']);

        $category = $results->firstWhere('type', 'Kategori');
        $this->assertNotNull($category);
        $this->assertTrue($results->contains('id', 'category-'.$transaction->category_id));

        $transactionResult = $results->firstWhere('type', 'Transaksi');
        $this->assertSame('transaction-'.$transaction->id, $transactionResult['id']);
        $this->assertSame($transaction->id, $transactionResult['transaction_id']);
    }

    public function test_transaction_result_has_complete_detail_payload()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);
        $transaction = $this->makeTransaction($user);

        $response = $this->actingAs($user)
            ->getJson(route('search.global', ['q' => 'Kopi Pagi']))
            ->assertOk();

        $item = collect($response->json('results'))->firstWhere('type', 'Transaksi');

        $this->assertNotNull($item);
        $this->assertSame('Kopi Pagi', $item['label']);
        $this->assertSame('Kopi Pagi', $item['subject']);
        $this->assertEquals(25000, $item['amount']);
        $this->assertTrue($item['is_cleared']);
        $this->assertFalse($item['is_draft']);
        $this->assertSame(
            Carbon::parse($transaction->date)->translatedFormat('d M Y'),
            $item['date'],
        );
        $this->assertSame($transaction->created_at->format('H:i'), $item['time']);
        $this->assertSame($transaction->due_date?->toDateString(), $item['due_date']);
        $this->assertSame('fixed', $item['due_date_type']);
        $this->assertEquals(5, $item['due_date_interval']);
        $this->assertSame('Expense', $item['transaction_type']['name']);
        $this->assertSame('Kopi', $item['category']['category_name']);
        $this->assertSame('Dompet Kopi', $item['source_wallet']['name']);
        $this->assertStringContainsString('Rp25.000', $item['description']);
        $this->assertStringContainsString($item['date'], $item['description']);
    }

    public function test_search_does_not_return_other_users_data()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);
        $other = User::factory()->create(['ui_theme' => 'default']);

        $this->makeTransaction($other, 'Rahasia Orang Lain');

        $response = $this->actingAs($user)
            ->getJson(route('search.global', ['q' => 'Rahasia']))
            ->assertOk();

        $this->assertSame([], $response->json('results'));
    }

    private function makeTransaction(User $user, string $subject = 'Kopi Pagi'): TransactionLog
    {
        $type = TransactionType::create([
            'user_id' => $user->id,
            'name' => 'Expense',
            'keyword' => 'expense',
        ]);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'name' => 'Dompet Kopi',
            'balance' => 100000,
            'group_type' => 'Liquid',
            'is_active' => true,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'type_id' => $type->id,
            'category_name' => 'Kopi',
            'icon' => 'coffee',
            'is_active' => true,
        ]);

        return TransactionLog::create([
            'reference_number' => 'TXNSRCH-'.strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'type_id' => $type->id,
            'category_id' => $category->id,
            'source_wallet_id' => $wallet->id,
            'amount' => 25000,
            'balance_before' => 100000,
            'balance_after' => 75000,
            'subject' => $subject,
            'notes' => 'Ngopi bareng tim',
            'is_cleared' => true,
            'due_date' => now()->addDays(5)->toDateString(),
            'due_date_type' => 'fixed',
            'due_date_interval' => 5,
        ]);
    }
}
