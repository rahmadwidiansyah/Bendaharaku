<?php

namespace Tests\Feature\Push;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    public function test_notifications_page_renders_with_preferences(): void
    {
        $this->user->update(['email_notifications' => false, 'push_notifications' => true]);

        $this->actingAs($this->user)
            ->get('/settings/application/notifications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Application/Notifications')
                ->has('emailNotifications')
                ->has('pushNotifications')
                ->has('vapidPublicKey'));
    }

    public function test_update_persists_preferences(): void
    {
        $this->actingAs($this->user)
            ->patchJson('/settings/application/notifications', [
                'email_notifications' => false,
                'push_notifications' => false,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->user->refresh();
        $this->assertFalse($this->user->email_notifications);
        $this->assertFalse($this->user->push_notifications);
    }

    public function test_update_requires_booleans(): void
    {
        $this->actingAs($this->user)
            ->patchJson('/settings/application/notifications', [
                'email_notifications' => 'not-bool',
                'push_notifications' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email_notifications');
    }
}
