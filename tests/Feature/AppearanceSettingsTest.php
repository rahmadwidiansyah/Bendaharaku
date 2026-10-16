<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function appearancePayload(array $overrides = []): array
    {
        return array_merge([
            'theme' => 'dark',
            'accent_color' => 'teal',
            'category_icon_colored' => true,
        ], $overrides);
    }

    public function test_patch_accepts_m3_ui_theme()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('settings.application.appearance.update'), $this->appearancePayload(['ui_theme' => 'm3']))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('m3', $user->fresh()->ui_theme);
    }

    public function test_patch_accepts_default_ui_theme()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->patchJson(route('settings.application.appearance.update'), $this->appearancePayload(['ui_theme' => 'default']))
            ->assertOk();

        $this->assertSame('default', $user->fresh()->ui_theme);
    }

    public function test_patch_rejects_invalid_ui_theme()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('settings.application.appearance.update'), $this->appearancePayload(['ui_theme' => 'material']))
            ->assertStatus(422);

        $this->assertSame('default', $user->fresh()->ui_theme);
    }

    public function test_patch_without_ui_theme_keeps_stored_value()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->patchJson(route('settings.application.appearance.update'), $this->appearancePayload())
            ->assertOk();

        $this->assertSame('m3', $user->fresh()->ui_theme);
    }

    public function test_patch_still_saves_other_appearance_fields()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('settings.application.appearance.update'), $this->appearancePayload([
                'theme' => 'light',
                'accent_color' => 'rose',
                'category_icon_colored' => false,
                'ui_theme' => 'm3',
            ]))
            ->assertOk();

        $fresh = $user->fresh();
        $this->assertSame('light', $fresh->theme);
        $this->assertSame('rose', $fresh->accent_color);
        $this->assertFalse((bool) $fresh->category_icon_colored);
        $this->assertSame('m3', $fresh->ui_theme);
    }

    public function test_auth_shared_props_include_ui_theme()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.ui_theme', 'm3')
                ->has('auth.user.theme'));
    }

    public function test_default_user_gets_default_dashboard_page()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('auth.user.ui_theme', 'default'));
    }

    public function test_m3_user_gets_material_dashboard_page()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Dashboard')
                ->where('auth.user.ui_theme', 'm3'));
    }

    public function test_dashboard_props_are_identical_for_both_views()
    {
        $expectedKeys = [
            'totalPortfolio', 'totalLiquid', 'totalInvest',
            'totalHutang', 'totalPiutang',
            'thisMonthIncome', 'thisMonthExpense',
            'transactions', 'dailyTotals', 'calendarMarkers',
            'listLimit', 'pinnedWallets', 'wallets',
            'startDate', 'endDate', 'filters', 'upcomingDebts',
        ];

        foreach (['default', 'm3'] as $uiTheme) {
            $user = User::factory()->create(['ui_theme' => $uiTheme]);

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk()
                ->assertInertia(fn ($page) => $page->hasAll($expectedKeys));
        }
    }
}
