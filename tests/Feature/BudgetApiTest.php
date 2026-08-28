<?php

namespace Tests\Feature;

use App\Enums\BudgetGenerationStatus;
use App\Jobs\GenerateBudgetJob;
use App\Models\BudgetGenerationStatus as BudgetGenerationStatusModel;
use App\Models\BudgetGroup;
use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\Budgeting\AIBudgetService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class BudgetApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
        $this->user->transactionLogs()->forceDelete();
    }

    private function createExpenseTransaction(Category $category, TransactionType $type, int $amount, string $date): TransactionLog
    {
        $wallet = $this->user->wallets()->firstOrFail();

        return TransactionLog::create([
            'user_id' => $this->user->id,
            'reference_number' => 'TRX-'.strtoupper(Str::random(10)),
            'date' => $date,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'source_wallet_id' => $wallet->id,
            'amount' => $amount,
            'balance_before' => 0,
            'balance_after' => 0,
            'subject' => 'Test expense',
            'is_cleared' => true,
        ]);
    }

    public function test_budgeting_page_renders_with_props(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/budgeting')
            ->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Budgeting/Index')
            ->has('categories')
            ->has('expenseGroups')
            ->where('botName', $this->user->bot_display_name));
    }

    public function test_unauthenticated_cannot_access_budget_endpoints(): void
    {
        $this->getJson('/api/v1/budget/2026/8')->assertUnauthorized();
        $this->postJson('/api/v1/budget/generate', ['year' => 2026, 'month' => 8])->assertUnauthorized();
        $this->getJson('/api/v1/budget/generate/status?year=2026&month=8')->assertUnauthorized();
        $this->putJson('/api/v1/budget/1', ['items' => []])->assertUnauthorized();
        $this->getJson('/api/v1/budget/settings')->assertUnauthorized();
    }

    public function test_settings_returns_bot_info_and_toggle_status(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/budget/settings')
            ->assertOk()
            ->assertJson([
                'auto_budget_enabled' => false,
                'bot_name' => $this->user->bot_display_name,
                'bot_avatar' => $this->user->bot_avatar_url,
            ]);
    }

    public function test_update_settings_toggles_auto_budget(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/budget/settings', ['auto_budget_enabled' => true])
            ->assertOk()
            ->assertJson(['message' => 'Settings updated successfully.']);

        $this->assertTrue((bool) $this->user->fresh()->auto_budget_enabled);

        $this->actingAs($this->user)
            ->getJson('/api/v1/budget/settings')
            ->assertJson(['auto_budget_enabled' => true]);
    }

    public function test_update_settings_rejects_invalid_payload(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/budget/settings', ['auto_budget_enabled' => 'not-a-boolean'])
            ->assertUnprocessable();
    }

    public function test_show_returns_404_when_no_budget_exists_for_period(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/budget/2026/8')
            ->assertNotFound();
    }

    public function test_show_returns_budget_with_spent_summary(): void
    {
        $category = $this->user->categories()->firstOrFail();
        $expenseType = TransactionType::where('name', 'Expense')->firstOrFail();

        $budgetGroup = BudgetGroup::create([
            'user_id' => $this->user->id,
            'period_month' => 8,
            'period_year' => 2026,
            'total_budget_amount' => 100000,
            'ai_notes' => 'Test notes',
            'generated_by' => 'ai',
        ]);

        $item = $budgetGroup->items()->create([
            'budgetable_id' => $category->id,
            'budgetable_type' => Category::class,
            'target_amount' => 100000,
        ]);

        $this->createExpenseTransaction($category, $expenseType, 40000, '2026-08-10');

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/budget/2026/8')
            ->assertOk();

        $response->assertJsonPath('id', $budgetGroup->id);
        $response->assertJsonPath("summary.{$item->id}.spent", 40000);
        $response->assertJsonPath("summary.{$item->id}.remaining", 60000);
    }

    public function test_user_cannot_view_another_users_budget(): void
    {
        $otherUser = User::factory()->create();

        BudgetGroup::create([
            'user_id' => $otherUser->id,
            'period_month' => 8,
            'period_year' => 2026,
            'total_budget_amount' => 50000,
            'ai_notes' => null,
            'generated_by' => 'manual',
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/budget/2026/8')
            ->assertNotFound();
    }

    public function test_generate_dispatches_job_and_returns_queued(): void
    {
        Queue::fake();

        $this->actingAs($this->user)
            ->postJson('/api/v1/budget/generate', ['year' => now()->year, 'month' => now()->month])
            ->assertStatus(202)
            ->assertJson([
                'queued' => true,
                'status' => BudgetGenerationStatus::Pending->value,
            ]);

        Queue::assertPushed(GenerateBudgetJob::class, fn ($job) => $job->userId === $this->user->id);

        $this->assertDatabaseHas('budget_generation_statuses', [
            'user_id' => $this->user->id,
            'year' => now()->year,
            'month' => now()->month,
            'status' => BudgetGenerationStatus::Pending->value,
        ]);
    }

    public function test_generate_budget_job_marks_status_completed(): void
    {
        $budgetGroup = BudgetGroup::create([
            'user_id' => $this->user->id,
            'period_month' => now()->month,
            'period_year' => now()->year,
            'total_budget_amount' => 50000,
            'ai_notes' => null,
            'generated_by' => 'manual',
        ]);

        $this->mock(AIBudgetService::class, function ($mock) use ($budgetGroup) {
            $mock->shouldReceive('generate')->once()->andReturn($budgetGroup);
        });

        $job = new GenerateBudgetJob($this->user->id, now()->month, now()->year);
        $job->handle(app(AIBudgetService::class));

        $this->assertDatabaseHas('budget_generation_statuses', [
            'user_id' => $this->user->id,
            'year' => now()->year,
            'month' => now()->month,
            'status' => BudgetGenerationStatus::Completed->value,
        ]);
    }

    public function test_generate_budget_job_marks_status_failed_on_error(): void
    {
        $this->mock(AIBudgetService::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andThrow(new RuntimeException('LLM timeout'));
        });

        $job = new GenerateBudgetJob($this->user->id, now()->month, now()->year);

        try {
            $job->handle(app(AIBudgetService::class));
        } catch (RuntimeException) {
            // handle() rethrow — status ditulis di failed()
        }

        $job->failed(new RuntimeException('LLM timeout'));

        $this->assertDatabaseHas('budget_generation_statuses', [
            'user_id' => $this->user->id,
            'year' => now()->year,
            'month' => now()->month,
            'status' => BudgetGenerationStatus::Failed->value,
            'error_message' => 'LLM timeout',
        ]);
    }

    public function test_generation_status_endpoint_returns_idle_when_no_generation(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/budget/generate/status?year='.now()->year.'&month='.now()->month)
            ->assertOk()
            ->assertJson(['status' => 'idle', 'error_message' => null]);
    }

    public function test_generation_status_endpoint_returns_current_status(): void
    {
        BudgetGenerationStatusModel::create([
            'user_id' => $this->user->id,
            'year' => now()->year,
            'month' => now()->month,
            'status' => BudgetGenerationStatus::Processing->value,
            'error_message' => null,
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/budget/generate/status?year='.now()->year.'&month='.now()->month)
            ->assertOk()
            ->assertJson([
                'status' => BudgetGenerationStatus::Processing->value,
                'error_message' => null,
            ]);
    }

    public function test_generate_rejects_past_month(): void
    {
        Queue::fake();

        $this->actingAs($this->user)
            ->postJson('/api/v1/budget/generate', ['year' => 2025, 'month' => 12])
            ->assertUnprocessable();

        Queue::assertNothingPushed();
    }

    public function test_generate_rejects_future_month(): void
    {
        Queue::fake();

        $this->actingAs($this->user)
            ->postJson('/api/v1/budget/generate', ['year' => now()->year + 1, 'month' => 1])
            ->assertUnprocessable();

        Queue::assertNothingPushed();
    }

    public function test_generate_validates_month_and_year(): void
    {
        Queue::fake();

        $this->actingAs($this->user)
            ->postJson('/api/v1/budget/generate', ['year' => 2026, 'month' => 13])
            ->assertUnprocessable();

        Queue::assertNothingPushed();
    }

    public function test_update_recalculates_total_budget(): void
    {
        $category = $this->user->categories()->firstOrFail();

        $budgetGroup = BudgetGroup::create([
            'user_id' => $this->user->id,
            'period_month' => 8,
            'period_year' => 2026,
            'total_budget_amount' => 100000,
            'ai_notes' => null,
            'generated_by' => 'ai',
        ]);

        $item = $budgetGroup->items()->create([
            'budgetable_id' => $category->id,
            'budgetable_type' => Category::class,
            'target_amount' => 100000,
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/budget/{$budgetGroup->id}", [
                'items' => [
                    ['id' => $item->id, 'target_amount' => 75000],
                ],
            ])
            ->assertOk();

        $this->assertSame('75000.00', $budgetGroup->fresh()->total_budget_amount);
    }
}
