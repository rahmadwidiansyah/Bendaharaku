<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSettingsHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_m3_user_gets_material_chat_settings_hub()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get(route('settings.chat.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Settings/Chat/Settings')
                ->has('emailNotifications')
                ->has('pushNotifications'));
    }

    public function test_default_user_gets_default_chat_settings_hub()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get(route('settings.chat.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Chat/Settings')
                ->has('emailNotifications')
                ->has('pushNotifications'));
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('settings.chat.index'))->assertRedirect(route('login'));
    }
}
