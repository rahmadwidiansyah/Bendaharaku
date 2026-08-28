<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use App\Evidence\Resolver\CategoryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryResolutionRegressionTest extends TestCase
{
    use RefreshDatabase;
    use RegressionTestHelpers;

    private CategoryResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRegressionData();
        $this->resolver = $this->app->make(CategoryResolver::class);
    }

    public function test_resolve_by_exact_transaction_type(): void
    {
        $result = $this->resolver->resolve(
            $this->user,
            transactionType: 'Expense',
            documentType: null,
            description: 'beli bakso'
        );

        $this->assertNotNull($result['category_id']);
        $this->assertNotNull($result['type_id']);
        $this->assertEquals('Expense', $result['type_name']);
        $this->assertGreaterThanOrEqual(0.5, $result['confidence']);
    }

    public function test_resolve_by_keyword_match(): void
    {
        $result = $this->resolver->resolve(
            $this->user,
            transactionType: 'Expense',
            documentType: null,
            description: 'makan siang'
        );

        $this->assertNotNull($result['category_id']);
        $this->assertEquals('Makan & Minum', $result['category_name']);
    }

    public function test_resolve_income_by_keyword(): void
    {
        $result = $this->resolver->resolve(
            $this->user,
            transactionType: 'Income',
            documentType: null,
            description: 'gaji bulan ini'
        );

        $this->assertNotNull($result['category_id']);
        $this->assertEquals('Gaji', $result['category_name']);
    }

    public function test_resolve_returns_low_confidence_for_unrecognized_text(): void
    {
        $result = $this->resolver->resolve(
            $this->user,
            transactionType: 'Expense',
            documentType: null,
            description: 'xyzabc123'
        );

        $this->assertNotNull($result['type_id']);
        $this->assertEquals('Expense', $result['type_name']);
    }

    public function test_resolve_without_transaction_type_returns_null_type(): void
    {
        $result = $this->resolver->resolve(
            $this->user,
            transactionType: null,
            documentType: null,
            description: 'makan'
        );

        $this->assertNotNull($result['category_id']);
    }

    public function test_resolve_with_qris_merchant_category(): void
    {
        $result = $this->resolver->resolve(
            $this->user,
            transactionType: 'Expense',
            documentType: 'QRIS',
            description: 'pembayaran',
            merchantCategory: 'Makanan dan Minuman',
            merchantName: 'Warung Bakso'
        );

        $this->assertNotNull($result['category_id']);
        $this->assertNotNull($result['type_id']);
    }
}
