<?php

namespace Tests\Feature\Push;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Push\PresenceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    public function test_subscribe_stores_subscription(): void
    {
        $this->actingAs($this->user)
            ->postJson('/notifications/subscribe', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
                'p256dh' => 'BEl62iUYgUivxIkv69yViEuiBIaIIKdsFRh',
                'auth' => 'a3Vr0H7XJzjqVfAq',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        ]);
    }

    public function test_subscribe_upserts_by_endpoint(): void
    {
        PushSubscription::create([
            'user_id' => $this->user->id,
            'endpoint' => 'https://example.com/endpoint',
            'p256dh' => 'old-key',
            'auth' => 'old-auth',
        ]);

        $this->actingAs($this->user)
            ->postJson('/notifications/subscribe', [
                'endpoint' => 'https://example.com/endpoint',
                'p256dh' => 'new-key',
                'auth' => 'new-auth',
            ])
            ->assertOk();

        $this->assertSame(1, PushSubscription::count());
        $this->assertDatabaseHas('push_subscriptions', ['p256dh' => 'new-key', 'auth' => 'new-auth']);
    }

    public function test_subscribe_requires_valid_endpoint(): void
    {
        $this->actingAs($this->user)
            ->postJson('/notifications/subscribe', [
                'endpoint' => 'not-a-url',
                'p256dh' => 'x',
                'auth' => 'y',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    }

    public function test_unsubscribe_deletes_own_subscription_only(): void
    {
        $other = User::factory()->create();
        $otherSub = PushSubscription::create([
            'user_id' => $other->id,
            'endpoint' => 'https://example.com/other',
            'p256dh' => 'x',
            'auth' => 'y',
        ]);
        $ownSub = PushSubscription::create([
            'user_id' => $this->user->id,
            'endpoint' => 'https://example.com/own',
            'p256dh' => 'x',
            'auth' => 'y',
        ]);

        $this->actingAs($this->user)
            ->postJson('/notifications/unsubscribe', ['endpoint' => $ownSub->endpoint])
            ->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['id' => $ownSub->id]);
        $this->assertDatabaseHas('push_subscriptions', ['id' => $otherSub->id]);
    }

    public function test_presence_marks_active_and_away(): void
    {
        $this->actingAs($this->user)
            ->postJson('/notifications/presence', ['state' => 'active'])
            ->assertOk();

        $this->assertFalse(app(PresenceService::class)->isAway($this->user->id));

        $this->actingAs($this->user)
            ->postJson('/notifications/presence', ['state' => 'away'])
            ->assertOk();

        $this->assertTrue(app(PresenceService::class)->isAway($this->user->id));
    }

    public function test_presence_rejects_invalid_state(): void
    {
        $this->actingAs($this->user)
            ->postJson('/notifications/presence', ['state' => 'maybe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('state');
    }
}
