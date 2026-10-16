<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsUiThemeTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED_PROPS = [
        'startDate', 'endDate',
        'totalIncome', 'totalExpense', 'totalDebt', 'totalReceivable',
        'cumulativeBalance',
        'expensesByCategory', 'incomesByCategory', 'debtsByCategory', 'receivablesByCategory',
        'dailyLabels', 'dailyIncome', 'dailyExpense', 'cumulativeData',
        'todayIndex', 'historyMode', 'openingBalance',
        'allDailyLabels', 'allDailyDates',
        'allDailyIncome', 'allDailyExpense', 'allDailyDebt', 'allDailyReceivable',
    ];

    public function test_default_user_gets_default_analytics_page()
    {
        $user = User::factory()->create(['ui_theme' => 'default']);

        $this->actingAs($user)
            ->get('/analytics')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Analytics/Index')
                ->where('auth.user.ui_theme', 'default')
                ->hasAll(self::EXPECTED_PROPS));
    }

    public function test_m3_user_gets_material_analytics_page()
    {
        $user = User::factory()->create(['ui_theme' => 'm3']);

        $this->actingAs($user)
            ->get('/analytics')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Material/Analytics')
                ->where('auth.user.ui_theme', 'm3')
                ->hasAll(self::EXPECTED_PROPS));
    }

    public function test_date_filter_params_are_preserved_for_both_views()
    {
        foreach (['default', 'm3'] as $uiTheme) {
            $user = User::factory()->create(['ui_theme' => $uiTheme]);

            $this->actingAs($user)
                ->get('/analytics?start_date=2026-09-01&end_date=2026-09-30')
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('startDate', '2026-09-01')
                    ->where('endDate', '2026-09-30'));
        }
    }
}
