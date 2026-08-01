<?php

namespace Tests\Feature\Push;

use App\Models\User;
use App\Services\Push\PushNotificationService;
use Database\Seeders\DatabaseSeeder;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\WebPush;
use Mockery;
use Tests\TestCase;

class PushNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
        config()->set('services.webpush.vapid_public_key', 'fake-public-key');
        config()->set('services.webpush.vapid_private_key', 'fake-private-key');
    }

    private function fakeWebPush(array $reports): WebPush
    {
        $mock = Mockery::mock(WebPush::class);
        $mock->shouldReceive('queueNotification')->once();
        $mock->shouldReceive('flush')->once()->andReturn((function () use ($reports) {
            yield from $reports;
        })());

        return $mock;
    }

    public function test_send_to_user_with_no_subscriptions_is_noop(): void
    {
        $this->user->pushSubscriptions()->delete();

        $service = new PushNotificationService;
        $service->sendToUser($this->user->id, ['title' => 'x', 'body' => 'y']);

        $this->assertTrue(true);
    }

    public function test_send_skips_when_push_disabled(): void
    {
        $this->user->pushSubscriptions()->create([
            'endpoint' => 'https://example.com/ep',
            'p256dh' => 'x',
            'auth' => 'y',
        ]);
        $this->user->update(['push_notifications' => false]);

        $service = new PushNotificationService;
        $service->sendToUser($this->user->id, ['title' => 'x', 'body' => 'y']);

        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://example.com/ep']);
    }

    public function test_send_deletes_expired_subscription_on_410(): void
    {
        $sub = $this->user->pushSubscriptions()->create([
            'endpoint' => 'https://example.com/expired',
            'p256dh' => 'x',
            'auth' => 'y',
        ]);

        $report = new MessageSentReport(
            new Request('POST', 'https://example.com/expired'),
            new Response(410),
            false,
            'Gone'
        );

        $service = new class($this->fakeWebPush([$report])) extends PushNotificationService
        {
            public function __construct(private readonly WebPush $fake) {}

            protected function makeWebPush(string $publicKey, string $privateKey): WebPush
            {
                return $this->fake;
            }
        };

        $service->sendToUser($this->user->id, ['title' => 'x', 'body' => 'y']);

        $this->assertDatabaseMissing('push_subscriptions', ['id' => $sub->id]);
    }
}
