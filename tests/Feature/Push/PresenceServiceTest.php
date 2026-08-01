<?php

namespace Tests\Feature\Push;

use App\Services\Push\PresenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_data_means_away(): void
    {
        $this->assertTrue(app(PresenceService::class)->isAway(999));
    }

    public function test_active_is_not_away(): void
    {
        $service = app(PresenceService::class);
        $service->markActive(1);

        $this->assertFalse($service->isAway(1));
    }

    public function test_away_is_away(): void
    {
        $service = app(PresenceService::class);
        $service->markAway(1);

        $this->assertTrue($service->isAway(1));
    }

    public function test_stale_active_becomes_away_after_threshold(): void
    {
        config()->set('bendaharaku.push.presence_ttl_seconds', 120);
        $service = app(PresenceService::class);
        $service->markActive(1);

        $this->travel(3)->minutes();

        $this->assertTrue($service->isAway(1));
    }

    public function test_fresh_active_stays_active(): void
    {
        $service = app(PresenceService::class);
        $service->markActive(1);

        $this->travel(1)->minute();

        $this->assertFalse($service->isAway(1));
    }
}
