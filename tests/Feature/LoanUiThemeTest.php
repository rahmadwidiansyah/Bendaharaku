<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanUiThemeTest extends TestCase
{
    use RefreshDatabase;

    private const INDEX_PROPS = ['loanDetails', 'title', 'isDebt', 'total'];

    public function test_default_user_gets_default_loans_debt_page()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get('/loans/debt')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Loans/Index')
                ->where('auth.user.ui_theme', 'default')
                ->where('isDebt', true)
                ->hasAll(self::INDEX_PROPS));
    }

    public function test_m3_user_gets_material_loans_debt_page()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get('/loans/debt')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Loans/Index')
                ->where('auth.user.ui_theme', 'm3')
                ->where('isDebt', true)
                ->hasAll(self::INDEX_PROPS));
    }

    public function test_default_user_gets_default_loans_receivable_page()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get('/loans/piutang')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Loans/Index')
                ->where('isDebt', false)
                ->hasAll(self::INDEX_PROPS));
    }

    public function test_m3_user_gets_material_loans_receivable_page()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get('/loans/piutang')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Loans/Index')
                ->where('isDebt', false)
                ->hasAll(self::INDEX_PROPS));
    }
}
