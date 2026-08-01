<?php

namespace Tests\Unit\Services\Loan;

use App\Models\TransactionLog;
use App\Services\Loan\DueDateService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DueDateServiceTest extends TestCase
{
    private DueDateService $service;

    private Carbon $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DueDateService;
        $this->today = Carbon::parse('2026-08-15')->startOfDay();
    }

    private function trx(array $overrides = []): TransactionLog
    {
        $base = [
            'id' => 1,
            'due_date' => null,
            'due_date_type' => null,
            'due_date_interval' => null,
            'date' => '2026-08-01',
        ];

        return new TransactionLog(array_merge($base, $overrides));
    }

    public function test_fixed(): void
    {
        $result = $this->service->nextDueDate(
            $this->trx(['due_date_type' => 'fixed', 'due_date' => '2026-08-20']),
            $this->today
        );

        $this->assertTrue($result->eq(Carbon::parse('2026-08-20')));
    }

    public function test_fixed_past_date_stays_past(): void
    {
        $result = $this->service->nextDueDate(
            $this->trx(['due_date_type' => 'fixed', 'due_date' => '2026-08-01']),
            $this->today
        );

        $this->assertTrue($result->eq(Carbon::parse('2026-08-01')));
    }

    public function test_monthly_takes_next_month_when_passed(): void
    {
        $result = $this->service->nextDueDate(
            $this->trx(['due_date_type' => 'monthly', 'due_date_interval' => 10]),
            $this->today
        );

        $this->assertTrue($result->eq(Carbon::parse('2026-09-10')));
    }

    public function test_monthly_current_month_when_not_passed(): void
    {
        $result = $this->service->nextDueDate(
            $this->trx(['due_date_type' => 'monthly', 'due_date_interval' => 20]),
            $this->today
        );

        $this->assertTrue($result->eq(Carbon::parse('2026-08-20')));
    }

    public function test_daily_interval(): void
    {
        $result = $this->service->nextDueDate(
            $this->trx(['due_date_type' => 'daily', 'due_date_interval' => 7, 'date' => '2026-08-01']),
            $this->today
        );

        $this->assertTrue($result->eq(Carbon::parse('2026-08-22')));
    }

    public function test_unknown_type_returns_null(): void
    {
        $this->assertNull($this->service->nextDueDate($this->trx(), $this->today));
    }
}
