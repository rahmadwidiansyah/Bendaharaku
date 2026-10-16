<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    private function inertiaProps($response): array
    {
        return $response->viewData('page')['props'];
    }

    public function test_range_mode_matches_full_mode_except_history_arrays()
    {
        $full = $this->actingAs($this->user)->get(route('analytics.index'));
        $full->assertOk();
        $range = $this->actingAs($this->user)->get(route('analytics.index', ['history' => 'range']));
        $range->assertOk();

        $fp = $this->inertiaProps($full);
        $rp = $this->inertiaProps($range);

        $this->assertEquals([], $rp['allDailyLabels']);
        $this->assertEquals([], $rp['allDailyDates']);
        $this->assertEquals('range', $rp['historyMode']);
        $this->assertEquals('full', $fp['historyMode']);
        $this->assertIsNumeric($rp['openingBalance']);

        // Semua props lain identik.
        foreach (['startDate', 'endDate', 'totalIncome', 'totalExpense', 'totalDebt', 'totalReceivable', 'cumulativeBalance', 'expensesByCategory', 'incomesByCategory', 'debtsByCategory', 'receivablesByCategory', 'dailyLabels', 'dailyIncome', 'dailyExpense', 'cumulativeData', 'todayIndex', 'openingBalance'] as $key) {
            $this->assertEquals($fp[$key], $rp[$key], "Prop {$key} berbeda antara mode full dan range.");
        }
    }

    public function test_history_endpoint_matches_full_mode_arrays()
    {
        $full = $this->actingAs($this->user)->get(route('analytics.index'));
        $fp = $this->inertiaProps($full);

        $res = $this->actingAs($this->user)->getJson(route('analytics.history'))->assertOk();

        foreach (['labels' => 'allDailyLabels', 'dates' => 'allDailyDates', 'income' => 'allDailyIncome', 'expense' => 'allDailyExpense', 'debt' => 'allDailyDebt', 'receivable' => 'allDailyReceivable'] as $json => $prop) {
            $this->assertEquals($fp[$prop], $res->json($json), "Array {$json} endpoint berbeda dari mode full.");
        }
    }

    public function test_invalid_history_value_falls_back_to_full()
    {
        $res = $this->actingAs($this->user)->get(route('analytics.index', ['history' => 'semua']));
        $res->assertOk();
        $this->assertEquals('full', $this->inertiaProps($res)['historyMode']);
    }

    public function test_history_endpoint_is_scoped_per_user()
    {
        $other = User::factory()->create();

        $mine = $this->actingAs($this->user)->getJson(route('analytics.history'))->assertOk();
        $theirs = $this->actingAs($other)->getJson(route('analytics.history'))->assertOk();

        // Pengguna lain tanpa histori: array kosong, bukan data milik user pertama.
        $this->assertEquals([], $theirs->json('dates'));
        $this->assertEquals($mine->json('dates'), $this->inertiaProps(
            $this->actingAs($this->user)->get(route('analytics.index'))
        )['allDailyDates']);
    }
}
