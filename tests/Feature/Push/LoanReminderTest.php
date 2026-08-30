<?php

namespace Tests\Feature\Push;

use App\Jobs\CheckLoanRemindersJob;
use App\Jobs\SendPushNotificationJob;
use App\Models\LoanReminder;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Loan\ActiveLoanCycleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LoanReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
        $this->user->pushSubscriptions()->create([
            'endpoint' => 'https://example.com/ep',
            'p256dh' => 'x',
            'auth' => 'y',
        ]);
        Queue::fake();
    }

    private function systemWallet(string $name): Wallet
    {
        return $this->user->wallets()->where('group_type', 'System')->where('name', 'like', "%{$name}%")->first();
    }

    private function createLoan(
        string $subject,
        string $systemKey,
        string $dueType,
        mixed $dueValue,
        string $date,
        float $amount = 1_000_000,
    ): TransactionLog {
        $category = $this->user->categories()->where('system_key', $systemKey)->firstOrFail();
        $type = TransactionType::where('name', $category->type->name)->firstOrFail();
        $cash = $this->user->wallets()->where('group_type', 'Liquid')->first();

        $data = [
            'reference_number' => 'TEST-'.uniqid(),
            'user_id' => $this->user->id,
            'date' => $date,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'source_wallet_id' => $systemKey === 'RECEIVABLE' ? $cash->id : $this->systemWallet('Hutang')->id,
            'destination_wallet_id' => $systemKey === 'RECEIVABLE' ? $this->systemWallet('Piutang')->id : $cash->id,
            'amount' => $amount,
            'balance_before' => 0,
            'balance_after' => 0,
            'subject' => strtoupper($subject),
            'is_cleared' => true,
            'due_date_type' => $dueType,
        ];
        if ($dueType === 'fixed') {
            $data['due_date'] = $dueValue;
        } else {
            $data['due_date_interval'] = $dueValue;
        }

        return TransactionLog::create($data);
    }

    public function test_fixed_due_today_dispatches_due_date_reminder(): void
    {
        $this->createLoan('Budi', 'LOAN', 'fixed', now()->toDateString(), now()->toDateString());

        (new CheckLoanRemindersJob($this->user->id, now()->toDateString()))->handle();

        Queue::assertPushed(SendPushNotificationJob::class, fn ($job) => $job->payload['url'] === '/loans/hutang');

        $this->assertDatabaseHas('loan_reminders', [
            'user_id' => $this->user->id,
            'subject' => 'BUDI',
            'loan_type' => 'debt',
            'reminder_type' => 'due_date',
            'due_date' => now()->toDateString(),
        ]);
    }

    public function test_fixed_due_tomorrow_dispatches_day_before_reminder(): void
    {
        $this->createLoan('Budi', 'LOAN', 'fixed', now()->addDay()->toDateString(), now()->toDateString());

        (new CheckLoanRemindersJob($this->user->id, now()->toDateString()))->handle();

        Queue::assertPushed(SendPushNotificationJob::class, fn ($job) => $job->payload['url'] === '/loans/hutang');
        $this->assertDatabaseHas('loan_reminders', ['reminder_type' => 'day_before']);
    }

    public function test_reminder_is_deduplicated(): void
    {
        $this->createLoan('Budi', 'LOAN', 'fixed', now()->toDateString(), now()->toDateString());

        $job = new CheckLoanRemindersJob($this->user->id, now()->toDateString());
        $job->handle();
        $job->handle();

        Queue::assertPushed(SendPushNotificationJob::class, 1);
        $this->assertSame(1, LoanReminder::count());
    }

    public function test_fixed_due_within_seven_days_dispatches_upcoming_reminder(): void
    {
        $this->createLoan('Budi', 'LOAN', 'fixed', now()->addDays(5)->toDateString(), now()->toDateString());

        (new CheckLoanRemindersJob($this->user->id, now()->toDateString()))->handle();

        Queue::assertPushed(SendPushNotificationJob::class, fn ($job) => $job->payload['url'] === '/loans/hutang');
        $this->assertDatabaseHas('loan_reminders', [
            'subject' => 'BUDI',
            'loan_type' => 'debt',
            'reminder_type' => 'upcoming',
        ]);
    }

    public function test_fixed_due_more_than_seven_days_is_not_reminded(): void
    {
        $this->createLoan('Budi', 'LOAN', 'fixed', now()->addDays(10)->toDateString(), now()->toDateString());

        (new CheckLoanRemindersJob($this->user->id, now()->toDateString()))->handle();

        Queue::assertNotPushed(SendPushNotificationJob::class);
        $this->assertSame(0, LoanReminder::count());
    }

    public function test_overdue_fixed_due_dispatches_overdue_reminder(): void
    {
        $this->createLoan('Budi', 'LOAN', 'fixed', now()->subDays(3)->toDateString(), now()->subDays(3)->toDateString());

        (new CheckLoanRemindersJob($this->user->id, now()->toDateString()))->handle();

        Queue::assertPushed(SendPushNotificationJob::class, fn ($job) => $job->payload['url'] === '/loans/hutang');
        $this->assertDatabaseHas('loan_reminders', [
            'subject' => 'BUDI',
            'loan_type' => 'debt',
            'reminder_type' => 'overdue',
        ]);
    }

    public function test_settled_loan_is_not_reminded(): void
    {
        $this->createLoan('Budi', 'LOAN', 'fixed', now()->toDateString(), now()->toDateString(), 1_000_000);

        $category = $this->user->categories()->where('system_key', 'DEBT_PAYMENT')->firstOrFail();
        $type = TransactionType::where('name', $category->type->name)->firstOrFail();
        $cash = $this->user->wallets()->where('group_type', 'Liquid')->first();

        TransactionLog::create([
            'reference_number' => 'TEST-'.uniqid(),
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'type_id' => $type->id,
            'category_id' => $category->id,
            'source_wallet_id' => $cash->id,
            'destination_wallet_id' => $this->systemWallet('Hutang')->id,
            'amount' => 1_000_000,
            'balance_before' => 0,
            'balance_after' => 0,
            'subject' => 'BUDI',
            'is_cleared' => true,
        ]);

        (new CheckLoanRemindersJob($this->user->id, now()->toDateString()))->handle();

        Queue::assertNotPushed(SendPushNotificationJob::class);
        $this->assertSame(0, LoanReminder::count());
    }

    public function test_new_loan_cycle_uses_new_due_date_after_previous_cycle_is_settled(): void
    {
        $today = now()->startOfDay();
        $this->createLoan('Budi', 'LOAN', 'fixed', $today->copy()->subDays(20)->toDateString(), $today->copy()->subDays(20)->toDateString());

        $category = $this->user->categories()->where('system_key', 'DEBT_PAYMENT')->firstOrFail();
        $type = TransactionType::where('name', $category->type->name)->firstOrFail();
        $cash = $this->user->wallets()->where('group_type', 'Liquid')->first();
        TransactionLog::create([
            'reference_number' => 'TEST-'.uniqid(),
            'user_id' => $this->user->id,
            'date' => $today->copy()->subDays(10)->toDateString(),
            'type_id' => $type->id,
            'category_id' => $category->id,
            'source_wallet_id' => $cash->id,
            'destination_wallet_id' => $this->systemWallet('Hutang')->id,
            'amount' => 1_000_000,
            'balance_before' => 0,
            'balance_after' => 0,
            'subject' => 'BUDI',
            'is_cleared' => true,
        ]);

        $this->createLoan('Budi', 'LOAN', 'fixed', $today->copy()->addDays(10)->toDateString(), $today->toDateString(), 500_000);

        $cycles = app(ActiveLoanCycleService::class)->calculateForUser($this->user, $today);
        $cycle = collect($cycles['debts'])->firstWhere('subject', 'BUDI');
        $this->assertNotNull($cycle);
        $this->assertSame(500000.0, $cycle['balance']);
        $this->assertSame($today->toDateString(), $cycle['since']);
        $this->assertSame($today->copy()->addDays(10)->toDateString(), $cycle['due_date']->toDateString());
    }

    public function test_new_loan_cycle_without_due_date_does_not_inherit_old_due_date(): void
    {
        $today = now()->startOfDay();
        $this->createLoan('Budi', 'LOAN', 'fixed', $today->copy()->subDays(20)->toDateString(), $today->copy()->subDays(20)->toDateString());

        $category = $this->user->categories()->where('system_key', 'DEBT_PAYMENT')->firstOrFail();
        $type = TransactionType::where('name', $category->type->name)->firstOrFail();
        $cash = $this->user->wallets()->where('group_type', 'Liquid')->first();
        TransactionLog::create([
            'reference_number' => 'TEST-'.uniqid(), 'user_id' => $this->user->id,
            'date' => $today->copy()->subDays(10)->toDateString(), 'type_id' => $type->id,
            'category_id' => $category->id, 'source_wallet_id' => $cash->id,
            'destination_wallet_id' => $this->systemWallet('Hutang')->id, 'amount' => 1_000_000,
            'balance_before' => 0, 'balance_after' => 0, 'subject' => 'BUDI', 'is_cleared' => true,
        ]);

        $loan = $this->createLoan('Budi', 'LOAN', 'fixed', null, $today->toDateString(), 500_000);
        $loan->update(['due_date_type' => null]);

        $cycles = app(ActiveLoanCycleService::class)->calculateForUser($this->user, $today);
        $this->assertNull($cycles['debts'][0]['due_date']);
    }

    public function test_monthly_recurring_is_reminded_per_instance(): void
    {
        $this->createLoan('Ani', 'RECEIVABLE', 'monthly', now()->day, now()->toDateString());

        (new CheckLoanRemindersJob($this->user->id, now()->toDateString()))->handle();

        Queue::assertPushed(SendPushNotificationJob::class, fn ($job) => $job->payload['url'] === '/loans/piutang');
        $this->assertDatabaseHas('loan_reminders', ['loan_type' => 'receivable', 'reminder_type' => 'due_date']);
    }
}
