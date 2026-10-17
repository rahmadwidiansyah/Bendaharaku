<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetingUiThemeTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED_PROPS = [
        'categories', 'expenseGroups', 'botName', 'botAvatar',
    ];

    public function test_default_user_gets_default_budgeting_page()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get('/budgeting')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Budgeting/Index')
                ->where('auth.user.ui_theme', 'default')
                ->hasAll(self::EXPECTED_PROPS));
    }

    public function test_m3_user_gets_material_budgeting_page()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get('/budgeting')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Budgeting/Index')
                ->where('auth.user.ui_theme', 'm3')
                ->hasAll(self::EXPECTED_PROPS));
    }
}
