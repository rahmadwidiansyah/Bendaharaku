<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use App\Evidence\Resolver\WalletResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Regression\RegressionTestHelpers;
use Tests\TestCase;

class WalletResolutionRegressionTest extends TestCase
{
    use RefreshDatabase;
    use RegressionTestHelpers;

    private WalletResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRegressionData();
        $this->resolver = $this->app->make(WalletResolver::class);
    }

    public function test_resolve_source_by_exact_name(): void
    {
        $result = $this->resolver->resolveSource($this->user, 'Dompet Cash', null);

        $this->assertNotNull($result['wallet_id']);
        $this->assertEquals('Dompet Cash', $result['wallet_name']);
        $this->assertGreaterThanOrEqual(0.9, $result['confidence']);
    }

    public function test_resolve_source_by_keyword(): void
    {
        $result = $this->resolver->resolveSource($this->user, null, 'BCA');

        $this->assertNotNull($result['wallet_id']);
        $this->assertEquals('BCA', $result['wallet_name']);
    }

    public function test_resolve_source_returns_null_for_unknown_wallet(): void
    {
        $result = $this->resolver->resolveSource($this->user, 'Tidak Ada', null);

        $this->assertNull($result['wallet_id']);
        $this->assertEquals('Tidak Ada', $result['wallet_name']);
    }

    public function test_resolve_destination_by_exact_name(): void
    {
        $result = $this->resolver->resolveDestination($this->user, 'Merchant System', null);

        $this->assertNotNull($result['wallet_id']);
        $this->assertEquals('Merchant System', $result['wallet_name']);
        $this->assertGreaterThanOrEqual(0.9, $result['confidence']);
    }

    public function test_resolve_destination_returns_system_wallet(): void
    {
        $result = $this->resolver->resolveDestination($this->user, 'Merchant', null);

        $this->assertNotNull($result['wallet_id']);
    }

    public function test_resolve_destination_returns_null_for_unknown(): void
    {
        $result = $this->resolver->resolveDestination($this->user, 'Unknown Wallet', null);

        $this->assertNull($result['wallet_id']);
        $this->assertNull($result['wallet_name']);
    }

    public function test_resolve_source_with_bank_name_matches_wallet(): void
    {
        $result = $this->resolver->resolveSource($this->user, null, 'bca');

        $this->assertNotNull($result['wallet_id']);
        $this->assertStringContainsStringIgnoringCase('BCA', $result['wallet_name'] ?? '');
    }
}
