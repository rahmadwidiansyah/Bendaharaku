<?php

namespace Tests\Feature;

use App\Models\BudgetGroup;
use App\Models\Category;
use App\Models\TransactionType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetingCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $expenseTypeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
        $this->expenseTypeId = TransactionType::where('name', 'Expense')->firstOrFail()->id;
    }

    private function createExpenseCategory(string $name): Category
    {
        return Category::create([
            'user_id' => $this->user->id,
            'type_id' => $this->expenseTypeId,
            'category_name' => $name,
            'icon' => 'folder',
            'is_active' => true,
        ]);
    }

    private function createAiBudget(array $rows, string $generatedBy = 'ai'): BudgetGroup
    {
        $budgetGroup = BudgetGroup::create([
            'user_id' => $this->user->id,
            'period_month' => now()->format('n'),
            'period_year' => now()->format('Y'),
            'total_budget_amount' => collect($rows)->sum('target_amount'),
            'ai_notes' => 'AI notes',
            'generated_by' => $generatedBy,
        ]);

        foreach ($rows as $row) {
            $budgetGroup->items()->create([
                'budgetable_id' => $row['category_id'],
                'budgetable_type' => Category::class,
                'target_amount' => $row['target_amount'],
            ]);
        }

        foreach (collect($rows)->groupBy('group_key') as $groupKey => $items) {
            $budgetGroup->expenseGroups()->create([
                'group_key' => $groupKey,
                'group_name' => (string) config("bendaharaku.budget.expense_groups.{$groupKey}", $groupKey),
                'category_ids' => $items->pluck('category_id')->all(),
            ]);
        }

        return $budgetGroup;
    }

    public function test_create_page_renders_with_categories_and_groups(): void
    {
        $response = $this->actingAs($this->user)->get('/budgeting/create')->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Budgeting/Create')
            ->has('categories')
            ->has('expenseGroups')
            ->where('existingBudget', null)
            ->where('botName', $this->user->bot_display_name));
    }

    public function test_create_page_prefills_existing_ai_budget(): void
    {
        $category = $this->createExpenseCategory('Makanan');
        $this->createAiBudget([
            ['category_id' => $category->id, 'target_amount' => 50000, 'group_key' => 'variable'],
        ]);

        $this->actingAs($this->user)
            ->get('/budgeting/create')
            ->assertInertia(fn ($page) => $page
                ->component('Budgeting/Create')
                ->where('existingBudget.generated_by', 'ai')
                ->where('existingBudget.items.0.budgetable_id', $category->id));
    }

    public function test_store_creates_manual_budget_without_existing(): void
    {
        $food = $this->createExpenseCategory('Makanan');
        $rent = $this->createExpenseCategory('Sewa Rumah');

        $response = $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'name' => 'Budget Bulanan',
                'rows' => [
                    ['category_id' => $food->id, 'group_key' => 'variable', 'target_amount' => 50000],
                    ['category_id' => $rent->id, 'group_key' => 'fixed', 'target_amount' => 200000],
                ],
            ])
            ->assertRedirect(route('budgeting.index'))
            ->assertSessionHas('success');

        $budgetGroup = BudgetGroup::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame('manual', $budgetGroup->generated_by);
        $this->assertSame('Budget Bulanan', $budgetGroup->name);
        $this->assertSame('250000.00', $budgetGroup->total_budget_amount);
        $this->assertSame(now()->format('n'), (string) $budgetGroup->period_month);
        $this->assertSame(2, $budgetGroup->items()->count());

        $variableGroup = $budgetGroup->expenseGroups()->where('group_key', 'variable')->firstOrFail();
        $fixedGroup = $budgetGroup->expenseGroups()->where('group_key', 'fixed')->firstOrFail();
        $this->assertSame([$food->id], $variableGroup->category_ids);
        $this->assertSame([$rent->id], $fixedGroup->category_ids);
    }

    public function test_store_merges_with_existing_ai_budget(): void
    {
        $food = $this->createExpenseCategory('Makanan');
        $rent = $this->createExpenseCategory('Sewa Rumah');

        $this->createAiBudget([
            ['category_id' => $food->id, 'target_amount' => 100000, 'group_key' => 'variable'],
            ['category_id' => $rent->id, 'target_amount' => 300000, 'group_key' => 'fixed'],
        ]);

        // User hanya mengubah kategori Makanan; Sewa Rumah tidak disentuh
        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'rows' => [
                    ['category_id' => $food->id, 'group_key' => 'sinking_fund', 'target_amount' => 75000],
                ],
            ])
            ->assertRedirect(route('budgeting.index'));

        $budgetGroup = BudgetGroup::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame('manual', $budgetGroup->generated_by);
        $this->assertSame('375000.00', $budgetGroup->total_budget_amount);

        $this->assertSame(2, $budgetGroup->items()->count());
        $foodItem = $budgetGroup->items()->where('budgetable_id', $food->id)->firstOrFail();
        $this->assertSame('75000.00', $foodItem->target_amount);
        $rentItem = $budgetGroup->items()->where('budgetable_id', $rent->id)->firstOrFail();
        $this->assertSame('300000.00', $rentItem->target_amount);

        $sinkingFundGroup = $budgetGroup->expenseGroups()->where('group_key', 'sinking_fund')->firstOrFail();
        $fixedGroup = $budgetGroup->expenseGroups()->where('group_key', 'fixed')->firstOrFail();
        $this->assertSame([$food->id], $sinkingFundGroup->category_ids);
        $this->assertSame([$rent->id], $fixedGroup->category_ids);
    }

    public function test_store_replaces_all_when_delete_ai_checked(): void
    {
        $food = $this->createExpenseCategory('Makanan');
        $rent = $this->createExpenseCategory('Sewa Rumah');

        $this->createAiBudget([
            ['category_id' => $food->id, 'target_amount' => 100000, 'group_key' => 'variable'],
            ['category_id' => $rent->id, 'target_amount' => 300000, 'group_key' => 'fixed'],
        ]);

        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'delete_ai' => true,
                'rows' => [
                    ['category_id' => $food->id, 'group_key' => 'variable', 'target_amount' => 90000],
                ],
            ])
            ->assertRedirect(route('budgeting.index'));

        $budgetGroup = BudgetGroup::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame('90000.00', $budgetGroup->total_budget_amount);
        $this->assertSame(1, $budgetGroup->items()->count());
        $this->assertSame([$food->id], $budgetGroup->items()->pluck('budgetable_id')->all());
    }

    public function test_store_replaces_existing_manual_budget(): void
    {
        $food = $this->createExpenseCategory('Makanan');
        $rent = $this->createExpenseCategory('Sewa Rumah');

        $this->createAiBudget([
            ['category_id' => $food->id, 'target_amount' => 50000, 'group_key' => 'variable'],
        ], generatedBy: 'manual');

        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'rows' => [
                    ['category_id' => $rent->id, 'group_key' => 'fixed', 'target_amount' => 100000],
                ],
            ])
            ->assertRedirect(route('budgeting.index'));

        $budgetGroup = BudgetGroup::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame(1, $budgetGroup->items()->count());
        $this->assertSame([$rent->id], $budgetGroup->items()->pluck('budgetable_id')->all());
    }

    public function test_store_without_name_saves_null(): void
    {
        $food = $this->createExpenseCategory('Makanan');

        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'rows' => [
                    ['category_id' => $food->id, 'group_key' => 'variable', 'target_amount' => 50000],
                ],
            ])
            ->assertRedirect(route('budgeting.index'));

        $budgetGroup = BudgetGroup::where('user_id', $this->user->id)->firstOrFail();
        $this->assertNull($budgetGroup->name);
    }

    public function test_store_detaches_category_with_empty_group(): void
    {
        $food = $this->createExpenseCategory('Makanan');

        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'rows' => [
                    ['category_id' => $food->id, 'group_key' => '', 'target_amount' => 50000],
                ],
            ])
            ->assertRedirect(route('budgeting.index'));

        $this->assertDatabaseCount('budget_items', 0);
        $this->assertDatabaseCount('budget_expense_groups', 0);
        $budgetGroup = BudgetGroup::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame('0.00', $budgetGroup->total_budget_amount);
    }

    public function test_store_validates_rows_required(): void
    {
        $this->actingAs($this->user)
            ->post('/budgeting/create', ['rows' => []])
            ->assertSessionHasErrors('rows');

        $this->assertDatabaseCount('budget_groups', 0);
    }

    public function test_store_validates_non_expense_category(): void
    {
        $incomeTypeId = TransactionType::where('name', 'Income')->firstOrFail()->id;
        $incomeCategory = Category::create([
            'user_id' => $this->user->id,
            'type_id' => $incomeTypeId,
            'category_name' => 'Gaji',
            'icon' => 'folder',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'rows' => [
                    ['category_id' => $incomeCategory->id, 'group_key' => 'variable', 'target_amount' => 50000],
                ],
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('budget_groups', 0);
    }

    public function test_store_validates_duplicate_category(): void
    {
        $food = $this->createExpenseCategory('Makanan');

        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'rows' => [
                    ['category_id' => $food->id, 'group_key' => 'variable', 'target_amount' => 50000],
                    ['category_id' => $food->id, 'group_key' => 'fixed', 'target_amount' => 30000],
                ],
            ])
            ->assertSessionHasErrors('rows.1.category_id');

        $this->assertDatabaseCount('budget_groups', 0);
    }

    public function test_store_validates_minimum_amount(): void
    {
        $food = $this->createExpenseCategory('Makanan');

        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'rows' => [
                    ['category_id' => $food->id, 'group_key' => 'variable', 'target_amount' => 0],
                ],
            ])
            ->assertSessionHasErrors('rows.0.target_amount');

        $this->assertDatabaseCount('budget_groups', 0);
    }

    public function test_store_validates_invalid_group_key(): void
    {
        $food = $this->createExpenseCategory('Makanan');

        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'rows' => [
                    ['category_id' => $food->id, 'group_key' => 'bogus', 'target_amount' => 50000],
                ],
            ])
            ->assertSessionHasErrors('rows.0.group_key');

        $this->assertDatabaseCount('budget_groups', 0);
    }

    public function test_store_rejects_foreign_category(): void
    {
        $otherUser = User::factory()->create();
        $foreignCategory = Category::create([
            'user_id' => $otherUser->id,
            'type_id' => $this->expenseTypeId,
            'category_name' => 'Milik Orang Lain',
            'icon' => 'folder',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'rows' => [
                    ['category_id' => $foreignCategory->id, 'group_key' => 'variable', 'target_amount' => 50000],
                ],
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('budget_groups', 0);
    }

    public function test_store_creates_custom_expense_group(): void
    {
        $food = $this->createExpenseCategory('Makanan');

        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'rows' => [
                    ['category_id' => $food->id, 'group_key' => 'custom', 'custom_group_name' => 'Cicilan', 'target_amount' => 75000],
                ],
            ])
            ->assertRedirect(route('budgeting.index'));

        $budgetGroup = BudgetGroup::where('user_id', $this->user->id)->firstOrFail();
        $group = $budgetGroup->expenseGroups()->firstOrFail();
        $this->assertSame('custom-cicilan', $group->group_key);
        $this->assertSame('Cicilan', $group->group_name);
        $this->assertSame([$food->id], $group->category_ids);
    }

    public function test_store_groups_same_custom_name_into_one_group(): void
    {
        $food = $this->createExpenseCategory('Makanan');
        $transport = $this->createExpenseCategory('Transport');

        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'rows' => [
                    ['category_id' => $food->id, 'group_key' => 'custom', 'custom_group_name' => 'Cicilan', 'target_amount' => 50000],
                    ['category_id' => $transport->id, 'group_key' => 'custom', 'custom_group_name' => 'Cicilan', 'target_amount' => 60000],
                ],
            ])
            ->assertRedirect(route('budgeting.index'));

        $budgetGroup = BudgetGroup::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame(1, $budgetGroup->expenseGroups()->count());
        $group = $budgetGroup->expenseGroups()->firstOrFail();
        $this->assertSame([$food->id, $transport->id], $group->category_ids);
        $this->assertSame('110000.00', $budgetGroup->total_budget_amount);
    }

    public function test_store_validates_custom_group_requires_name(): void
    {
        $food = $this->createExpenseCategory('Makanan');

        $this->actingAs($this->user)
            ->post('/budgeting/create', [
                'rows' => [
                    ['category_id' => $food->id, 'group_key' => 'custom', 'custom_group_name' => '', 'target_amount' => 50000],
                ],
            ])
            ->assertSessionHasErrors('rows.0.custom_group_name');

        $this->assertDatabaseCount('budget_groups', 0);
    }

    public function test_store_requires_auth(): void
    {
        $this->get('/budgeting/create')->assertRedirect('/login');
        $this->post('/budgeting/create')->assertRedirect('/login');
    }
}
