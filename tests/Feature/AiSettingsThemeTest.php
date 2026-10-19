<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiSettingsThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_m3_user_gets_material_bot_page_with_same_props()
    {
        $user = User::factory()->create([
            'ui_theme' => 'm3',
            'bot_name' => 'Ken-Chan',
            'bot_avatar' => null,
        ]);

        $this->actingAs($user)
            ->get(route('settings.ai.bot'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Settings/AI/Bot')
                ->where('botName', 'Ken-Chan')
                ->has('botAvatar'));
    }

    public function test_default_user_gets_default_bot_page()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get(route('settings.ai.bot'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/AI/Bot')
                ->has('botName')
                ->has('botAvatar'));
    }

    public function test_m3_user_gets_material_memory_page()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get(route('settings.ai.memory'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Material/Settings/AI/Memory'));
    }

    public function test_default_user_gets_default_memory_page()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get(route('settings.ai.memory'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/AI/Memory'));
    }

    public function test_m3_user_gets_material_models_page_without_unused_log_props()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get(route('settings.ai.models'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Settings/AI/Models')
                ->has('aiAccounts')
                ->has('aiRouting')
                ->has('aiCandidates')
                ->has('aiSkipped')
                ->missing('usageStats')
                ->missing('recentLogs'));
    }

    public function test_default_user_gets_default_models_page()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get(route('settings.ai.models'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/AI/Models')
                ->has('aiAccounts')
                ->has('aiRouting')
                ->missing('usageStats')
                ->missing('recentLogs'));
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('settings.ai.bot'))->assertRedirect(route('login'));
        $this->get(route('settings.ai.memory'))->assertRedirect(route('login'));
        $this->get(route('settings.ai.models'))->assertRedirect(route('login'));
    }
}
