<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiProviderStatusPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_m3_user_gets_material_status_page_with_provider_data()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get(route('settings.ai.status'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Settings/AI/Status')
                ->has('accounts')
                ->has('method')
                ->has('candidates')
                ->has('skipped'));
    }

    public function test_default_user_is_redirected_to_models_page()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get(route('settings.ai.status'))
            ->assertRedirect(route('settings.ai.models'));
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('settings.ai.status'))->assertRedirect(route('login'));
    }
}
