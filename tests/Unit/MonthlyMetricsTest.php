<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Collection;

class MonthlyMetricsTest extends TestCase
{
    public function testBuildMonthlyMetricsHandlesArrayAndCollection()
    {
        // instantiate service without constructor so we can call private methods
        $ref = new \ReflectionClass(\App\Chat\ChatApplicationService::class);
        $service = $ref->newInstanceWithoutConstructor();

        // prepare fake transactions as array
        $tx = new \stdClass();
        $tx->amount = 10000;
        $tx->type = (object)['name' => 'expense'];
        $tx->is_cleared = true;
        $tx->created_at = '2026-07-19 10:00:00';
        $tx->category = (object)['category_name' => 'Makan'];

        $method = $ref->getMethod('buildMonthlyMetrics');
        $method->setAccessible(true);

        // call with array
        $resultArray = $method->invoke($service, [$tx]);
        $this->assertInstanceOf(Collection::class, $resultArray);

        // call with Collection
        $resultCollection = $method->invoke($service, Collection::make([$tx]));
        $this->assertInstanceOf(Collection::class, $resultCollection);
    }

    public function testBuildLocalMonthlyReportHandlesEmpty()
    {
        $ref = new \ReflectionClass(\App\Chat\ChatApplicationService::class);
        $service = $ref->newInstanceWithoutConstructor();

        $method = $ref->getMethod('buildLocalMonthlyReport');
        $method->setAccessible(true);

        $result = $method->invoke($service, []);
        $this->assertIsArray($result);
    }
}
