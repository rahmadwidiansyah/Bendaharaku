<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatUiThemeTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED_PROPS = [
        'conversation', 'initialMessages', 'initialHasMore', 'botProfile', 'commands',
    ];

    private function makeUser(string $theme): User
    {
        return User::factory()->create(['ui_theme' => $theme]);
    }

    public function test_default_user_gets_default_chat_page()
    {
        $user = $this->makeUser('default');

        $this->actingAs($user)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Chat/Index')
                ->where('auth.user.ui_theme', 'default')
                ->hasAll(self::EXPECTED_PROPS));
    }

    public function test_m3_user_gets_material_chat_page()
    {
        $user = $this->makeUser('m3');

        $this->actingAs($user)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Chat/Index')
                ->where('auth.user.ui_theme', 'm3')
                ->hasAll(self::EXPECTED_PROPS));
    }
}
