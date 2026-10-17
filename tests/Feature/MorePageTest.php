<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MorePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_m3_user_gets_material_more_page()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get(route('more'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/More')
                ->where('auth.user.ui_theme', 'm3'));
    }

    public function test_default_user_gets_default_more_page()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get(route('more'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('More')
                ->where('auth.user.ui_theme', 'default'));
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('more'))->assertRedirect(route('login'));
    }
}
