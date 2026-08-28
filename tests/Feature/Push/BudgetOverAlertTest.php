<?php

namespace Tests\Feature\Push;

use App\Jobs\CheckBudgetAlertsJob;
use App\Jobs\SendPushNotificationJob;
use App\Models\BudgetExpenseGroup;
use App\Models\BudgetGroup;
use App\Models\BudgetItem;
use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BudgetOverAlertTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BudgetGroup $group;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
        $this->user->transactionLogs()->forceDelete();
        $this->user->pushSubscriptions()->create([
            'endpoint' => 'https://example.com/ep',
            'p256dh' => 'x',
            'auth' => 'y',
        ]);

        $this->category = $this->user->categories()->firstWhere('category_name', 'Makan & Minum')
            ?? $this->user->categories()->first();

        $this->group = BudgetGroup::create([
            'user_id' => $this->user->id,
            'period_month' => now()->month,
            'period_year' => now()->year,
            'total_budget_amount' => 100_000,
            'generated_by' => 'manual',
        ]);
        BudgetItem::create([
            'budget_group_id' => $this->group->id,
            'budgetable_id' => $this->category->id,
            'budgetable_type' => Category::class,
            'target_amount' => 100_000,
        ]);
        BudgetExpenseGroup::create([
            'budget_group_id' => $this->group->id,
            'group_key' => 'variable',
            'group_name' => 'Biaya Variabel',
            'category_ids' => [$this->category->id],
        ]);
    }

    private function createExpense(float $amount): TransactionLog
    {
        $expenseType = TransactionType::where('name', 'Expense')->firstOrFail();
        $cashWallet = $this->user->wallets()->where('group_type', 'Liquid')->first();
        $merchant = $this->user->wallets()->where('group_type', 'System')->where('name', 'like', '%Merchant%')->first();

        return TransactionLog::create([
            'reference_number' => 'TEST-'.uniqid(),
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'type_id' => $expenseType->id,
            'category_id' => $this->category->id,
            'source_wallet_id' => $cashWallet->id,
            'destination_wallet_id' => $merchant->id,
            'amount' => $amount,
            'balance_before' => 0,
            'balance_after' => 0,
            'subject' => 'test',
            'is_cleared' => true,
        ]);
    }

    public function test_over_budget_dispatches_push_once(): void
    {
        Queue::fake();

        $this->createExpense(150_000);

        (new CheckBudgetAlertsJob($this->user->id, now()->month, now()->year))->handle();

        Queue::assertPushed(SendPushNotificationJob::class, fn ($job) => $job->payload['url'] === '/budgeting');

        $this->group->refresh();
        $this->assertNotNull($this->group->over_alert_sent_at);
    }

    public function test_alert_is_idempotent(): void
    {
        Queue::fake();

        $this->createExpense(150_000);

        $job = new CheckBudgetAlertsJob($this->user->id, now()->month, now()->year);
        $job->handle();
        $job->handle();

        Queue::assertPushed(SendPushNotificationJob::class, 1);
    }

    public function test_no_alert_when_budget_not_exceeded(): void
    {
        Queue::fake();

        $this->createExpense(50_000);

        (new CheckBudgetAlertsJob($this->user->id, now()->month, now()->year))->handle();

        Queue::assertNotPushed(SendPushNotificationJob::class);
        $this->group->refresh();
        $this->assertNull($this->group->over_alert_sent_at);
    }

    public function test_no_alert_when_no_budget_group(): void
    {
        Queue::fake();

        BudgetGroup::query()->delete();
        $this->createExpense(150_000);

        (new CheckBudgetAlertsJob($this->user->id, now()->month, now()->year))->handle();

        Queue::assertNotPushed(SendPushNotificationJob::class);
    }
}
