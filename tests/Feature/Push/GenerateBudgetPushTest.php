<?php

namespace Tests\Feature\Push;

use App\Jobs\GenerateBudgetJob;
use App\Jobs\SendPushNotificationJob;
use App\Models\BudgetGroup;
use App\Models\User;
use App\Services\Budgeting\AIBudgetService;
use App\Services\Push\PresenceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class GenerateBudgetPushTest extends TestCase
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
    }

    public function test_success_dispatches_push_when_away(): void
    {
        Queue::fake();
        $this->mock(PresenceService::class, fn ($mock) => $mock->shouldReceive('isAway')->andReturn(true));

        $this->mock(AIBudgetService::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andReturn(Mockery::mock(BudgetGroup::class));
        });

        (new GenerateBudgetJob($this->user->id, now()->month, now()->year))->handle(app(AIBudgetService::class));

        Queue::assertPushed(SendPushNotificationJob::class, fn ($job) => $job->payload['url'] === '/budgeting');
    }

    public function test_success_skips_push_when_active(): void
    {
        Queue::fake();
        $this->mock(PresenceService::class, fn ($mock) => $mock->shouldReceive('isAway')->andReturn(false));

        $this->mock(AIBudgetService::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andReturn(Mockery::mock(BudgetGroup::class));
        });

        (new GenerateBudgetJob($this->user->id, now()->month, now()->year))->handle(app(AIBudgetService::class));

        Queue::assertNotPushed(SendPushNotificationJob::class);
    }

    public function test_failed_job_dispatches_failure_push_when_away(): void
    {
        Queue::fake();
        $this->mock(PresenceService::class, fn ($mock) => $mock->shouldReceive('isAway')->andReturn(true));

        $job = new GenerateBudgetJob($this->user->id, now()->month, now()->year);
        $job->failed(new \RuntimeException('AI down'));

        Queue::assertPushed(SendPushNotificationJob::class, fn ($job) => $job->payload['url'] === '/budgeting');
    }
}
