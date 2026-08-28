<?php

namespace Tests\Unit;

use App\Chat\Services\MonthlyReportService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MonthlyMetricsTest extends TestCase
{
    public function test_build_monthly_metrics_handles_array_and_collection()
    {
        // instantiate service without constructor so we can call private methods
        $ref = new \ReflectionClass(MonthlyReportService::class);
        $service = $ref->newInstanceWithoutConstructor();

        // prepare fake transactions as array
        $tx = new \stdClass;
        $tx->amount = 10000;
        $tx->type = (object) ['name' => 'expense'];
        $tx->is_cleared = true;
        $tx->created_at = '2026-07-19 10:00:00';
        $tx->category = (object) ['category_name' => 'Makan'];

        $method = $ref->getMethod('buildMonthlyMetrics');
        $method->setAccessible(true);

        // call with array
        $resultArray = $method->invoke($service, [$tx]);
        $this->assertInstanceOf(Collection::class, $resultArray);

        // call with Collection
        $resultCollection = $method->invoke($service, Collection::make([$tx]));
        $this->assertInstanceOf(Collection::class, $resultCollection);
    }

    public function test_build_local_monthly_report_handles_empty()
    {
        $ref = new \ReflectionClass(MonthlyReportService::class);
        $service = $ref->newInstanceWithoutConstructor();

        $method = $ref->getMethod('buildLocalMonthlyReport');
        $method->setAccessible(true);

        $result = $method->invoke($service, []);
        $this->assertIsArray($result);
    }
}
