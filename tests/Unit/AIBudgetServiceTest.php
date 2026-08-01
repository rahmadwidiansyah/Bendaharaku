<?php

namespace Tests\Unit;

use App\Enums\AiProvider;
use App\Models\BudgetGroup;
use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\UserAiCredential;
use App\Models\UserAiPreference;
use App\Services\AI\Adapters\DeepSeekAdapter;
use App\Services\AI\Adapters\GeminiAdapter;
use App\Services\AI\Adapters\OpenAIAdapter;
use App\Services\AI\Adapters\OpenAiCompatibleAdapter;
use App\Services\AI\AiCredentialManager;
use App\Services\AI\AiPreferenceManager;
use App\Services\AI\AiProviderFactory;
use App\Services\Budgeting\AIBudgetService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class AIBudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $category;

    private TransactionType $expenseType;

    private DeepSeekAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
        $this->expenseType = TransactionType::where('name', 'Expense')->firstOrFail();
        $this->category = $this->user->categories()->where('type_id', $this->expenseType->id)->firstOrFail();

        UserAiPreference::create([
            'user_id' => $this->user->id,
            'provider' => AiProvider::DeepSeek,
            'selected_model' => 'deepseek-chat',
            'is_active_provider' => true,
        ]);

        UserAiCredential::create([
            'user_id' => $this->user->id,
            'provider' => AiProvider::DeepSeek,
            'api_key' => 'test-api-key',
            'is_valid' => true,
        ]);

        $this->adapter = $this->mock(DeepSeekAdapter::class);

        config()->set('bendaharaku.ai.openai_compatible.api_key', '');
        config()->set('bendaharaku.ai.openai_compatible.base_url', '');
    }

    private function createExpense(int $amount, string $date, ?Category $category = null): TransactionLog
    {
        $wallet = $this->user->wallets()->firstOrFail();

        return TransactionLog::create([
            'user_id' => $this->user->id,
            'reference_number' => 'TRX-'.strtoupper(Str::random(10)),
            'date' => $date,
            'type_id' => $this->expenseType->id,
            'category_id' => $category?->id ?? $this->category->id,
            'source_wallet_id' => $wallet->id,
            'amount' => $amount,
            'balance_before' => 0,
            'balance_after' => 0,
            'subject' => 'Test expense',
            'is_cleared' => true,
        ]);
    }

    private function makeService(): AIBudgetService
    {
        // AiProviderFactory is readonly and type-hints concrete adapters,
        // so we pass mocked concrete adapters into a real factory.
        $factory = new AiProviderFactory(
            $this->mock(GeminiAdapter::class),
            $this->mock(OpenAIAdapter::class),
            $this->adapter,
            $this->mock(OpenAiCompatibleAdapter::class),
        );

        return new AIBudgetService(
            $factory,
            new AiPreferenceManager,
            new AiCredentialManager,
        );
    }

    public function test_generate_creates_budget_group_and_items_from_ai_response(): void
    {
        $this->createExpense(100000, '2026-05-15');
        $this->createExpense(50000, '2026-06-20');

        $this->adapter->shouldReceive('generateText')->once()->andReturn(
            json_encode([
                'notes' => 'Keep spending low.',
                'by_category' => [['id' => $this->category->id, 'amount' => 120000]],
                'by_type' => [['id' => $this->expenseType->id, 'amount' => 130000]],
            ])
        );

        $service = $this->makeService();
        $group = $service->generate($this->user, 8, 2026);

        $this->assertInstanceOf(BudgetGroup::class, $group);
        $this->assertSame('120000.00', $group->total_budget_amount);
        $this->assertSame('Keep spending low.', $group->ai_notes);
        $this->assertSame('ai', $group->generated_by);
        $this->assertCount(1, $group->items);
    }

    public function test_regenerate_replaces_items_instead_of_duplicating(): void
    {
        $this->createExpense(100000, '2026-05-15');

        $this->adapter->shouldReceive('generateText')->twice()->andReturn(
            json_encode([
                'notes' => 'First run.',
                'by_category' => [['id' => $this->category->id, 'amount' => 120000]],
                'by_type' => [['id' => $this->expenseType->id, 'amount' => 130000]],
            ]),
            json_encode([
                'notes' => 'Second run.',
                'by_category' => [['id' => $this->category->id, 'amount' => 90000]],
                'by_type' => [],
            ])
        );

        $service = $this->makeService();
        $first = $service->generate($this->user, 8, 2026);
        $second = $service->generate($this->user, 8, 2026);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, BudgetGroup::where('user_id', $this->user->id)->where('period_month', 8)->where('period_year', 2026)->count());
        $this->assertSame('90000.00', $second->fresh()->total_budget_amount);
        $this->assertCount(1, $second->fresh('items')->items);
        $this->assertSame('Second run.', $second->ai_notes);
    }

    public function test_generate_throws_when_no_active_ai_preference(): void
    {
        UserAiPreference::where('user_id', $this->user->id)->delete();

        $this->adapter->shouldReceive('generateText')->never();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('preferensi AI');

        $this->makeService()->generate($this->user, 8, 2026);
    }

    public function test_generate_throws_when_no_valid_api_key(): void
    {
        UserAiCredential::where('user_id', $this->user->id)->delete();

        $this->adapter->shouldReceive('generateText')->never();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API Key');

        $this->makeService()->generate($this->user, 8, 2026);
    }

    public function test_generate_throws_when_ai_returns_invalid_format(): void
    {
        $this->adapter->shouldReceive('generateText')->once()->andReturn('not json');

        $this->expectException(\UnexpectedValueException::class);

        $this->makeService()->generate($this->user, 8, 2026);
    }

    public function test_get_budget_summary_calculates_spent_and_remaining(): void
    {
        $category = Category::create([
            'user_id' => $this->user->id,
            'type_id' => $this->expenseType->id,
            'category_name' => 'Kategori Uji Summary',
            'icon' => 'folder',
        ]);

        $this->createExpense(40000, '2026-08-10', $category);

        $group = BudgetGroup::create([
            'user_id' => $this->user->id,
            'period_month' => 8,
            'period_year' => 2026,
            'total_budget_amount' => 100000,
            'ai_notes' => null,
            'generated_by' => 'ai',
        ]);

        $item = $group->items()->create([
            'budgetable_id' => $category->id,
            'budgetable_type' => Category::class,
            'target_amount' => 100000,
        ]);

        $summary = $this->makeService()->getBudgetSummary($group);

        $this->assertSame(40000.0, $summary[$item->id]['spent']);
        $this->assertSame(60000.0, $summary[$item->id]['remaining']);
        $this->assertSame(40.0, $summary[$item->id]['percentage']);
    }

    public function test_generate_uses_default_openai_compatible_provider_when_no_active_preference(): void
    {
        UserAiPreference::where('user_id', $this->user->id)->delete();
        UserAiCredential::where('user_id', $this->user->id)->delete();

        config()->set('bendaharaku.ai.openai_compatible.api_key', 'env-default-key');
        config()->set('bendaharaku.ai.openai_compatible.base_url', 'https://example.com/v1/chat/completions');
        config()->set('bendaharaku.ai.openai_compatible.model', 'gpt-4o-mini');

        $this->createExpense(100000, '2026-05-15');

        $compatAdapter = $this->mock(OpenAiCompatibleAdapter::class);
        $compatAdapter->shouldReceive('generateText')->once()->andReturn(
            json_encode([
                'notes' => 'Default provider run.',
                'by_category' => [['id' => $this->category->id, 'amount' => 120000]],
                'by_type' => [],
            ])
        );

        $factory = new AiProviderFactory(
            $this->mock(GeminiAdapter::class),
            $this->mock(OpenAIAdapter::class),
            $this->mock(DeepSeekAdapter::class),
            $compatAdapter,
        );

        $service = new AIBudgetService($factory, new AiPreferenceManager, new AiCredentialManager);
        $group = $service->generate($this->user, 8, 2026);

        $this->assertSame('120000.00', $group->total_budget_amount);
    }

    public function test_generate_classifies_categories_into_expense_groups(): void
    {
        $secondCategory = Category::create([
            'user_id' => $this->user->id,
            'type_id' => $this->expenseType->id,
            'category_name' => 'Skincare',
            'icon' => '🧴',
        ]);

        $this->adapter->shouldReceive('generateText')->once()->andReturn(
            json_encode([
                'notes' => 'Grouped.',
                'by_category' => [
                    ['id' => $this->category->id, 'amount' => 100000],
                    ['id' => $secondCategory->id, 'amount' => 50000],
                ],
                'expense_groups' => [
                    ['group' => 'fixed', 'category_ids' => [$this->category->id]],
                    ['group' => 'variable', 'category_ids' => [$secondCategory->id]],
                ],
            ])
        );

        $group = $this->makeService()->generate($this->user, 8, 2026);

        $groups = $group->expenseGroups;
        $this->assertCount(2, $groups);

        $fixed = $groups->firstWhere('group_key', 'fixed');
        $this->assertSame('Biaya Tetap', $fixed->group_name);
        $this->assertSame([$this->category->id], $fixed->category_ids);

        $variable = $groups->firstWhere('group_key', 'variable');
        $this->assertSame('Biaya Variabel', $variable->group_name);
        $this->assertSame([$secondCategory->id], $variable->category_ids);
    }

    public function test_generate_ignores_invalid_expense_group_assignments(): void
    {
        $this->adapter->shouldReceive('generateText')->once()->andReturn(
            json_encode([
                'notes' => 'Grouped.',
                'by_category' => [['id' => $this->category->id, 'amount' => 100000]],
                'expense_groups' => [
                    ['group' => 'unknown_group', 'category_ids' => [$this->category->id]],
                    ['group' => 'fixed', 'category_ids' => [999999]],
                ],
            ])
        );

        $group = $this->makeService()->generate($this->user, 8, 2026);

        $this->assertCount(0, $group->expenseGroups);
    }
}
