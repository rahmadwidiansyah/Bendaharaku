<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Services\AI\Context\AIContextBuilder;
use App\Services\AI\Context\ContextOptions;
use App\Services\AI\Context\ContextSnapshot;
use App\Services\AI\Context\RuleContextBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class AIContextBuilderTest extends TestCase
{
    private AIContextBuilder $aiBuilder;

    private RuleContextBuilder $ruleBuilder;

    protected function setUp(): void
    {
        $this->aiBuilder = new AIContextBuilder;
        $this->ruleBuilder = new RuleContextBuilder;
    }

    public function test_rule_context_passes_all_data_through(): void
    {
        $snapshot = $this->makeSnapshot([
            'wallets' => [['id' => 1, 'name' => 'Cash', 'keyword' => 'cash', 'balance' => 50000]],
            'categories' => [['id' => 1, 'category_name' => 'Makanan', 'keyword' => 'makan']],
        ]);

        $context = $this->ruleBuilder->build($snapshot);

        $this->assertCount(1, $context->wallets);
        $this->assertCount(1, $context->categories);
        $this->assertSame('Cash', $context->wallets->first()->name);
        $this->assertSame('Makanan', $context->categories->first()->category_name);
    }

    public function test_ai_context_strips_balances_and_prunes(): void
    {
        $snapshot = $this->makeSnapshot([
            'wallets' => [
                ['id' => 1, 'name' => 'Cash', 'keyword' => 'cash', 'group_type' => 'Personal', 'is_pinned' => false, 'balance' => 50000],
                ['id' => 2, 'name' => 'External System', 'keyword' => '', 'group_type' => 'System', 'is_pinned' => false, 'balance' => 0],
                ['id' => 3, 'name' => 'BCA', 'keyword' => 'bca', 'group_type' => 'Personal', 'is_pinned' => true, 'balance' => 100000],
            ],
            'categories' => [
                ['id' => 1, 'category_name' => 'Makanan', 'keyword' => 'makan'],
                ['id' => 2, 'category_name' => 'Transport', 'keyword' => 'bensin'],
            ],
        ]);

        $context = $this->aiBuilder->build($snapshot);

        $this->assertCount(2, $context->wallets);
        $this->assertArrayNotHasKey('balance', $context->wallets[0]);
        $this->assertArrayNotHasKey('keyword', $context->wallets[0]);

        $this->assertSame('BCA', $context->wallets[0]['name']);
        $this->assertSame('Cash', $context->wallets[1]['name']);
    }

    public function test_ai_context_builds_keyword_aliases(): void
    {
        $snapshot = $this->makeSnapshot([
            'wallets' => [
                ['id' => 1, 'name' => 'ShopeePay', 'keyword' => 'spay,shopeepay', 'group_type' => 'Personal', 'is_pinned' => false, 'balance' => 0],
            ],
            'categories' => [
                ['id' => 1, 'category_name' => 'Makan & Minum', 'keyword' => 'makan,minum,kuliner'],
            ],
        ]);

        $context = $this->aiBuilder->build($snapshot);

        $this->assertSame('ShopeePay', $context->keywordAliases['spay']);
        $this->assertSame('ShopeePay', $context->keywordAliases['shopeepay']);
        $this->assertSame('ShopeePay', $context->keywordAliases['shopeepay']);
        $this->assertSame('Makan & Minum', $context->keywordAliases['makan']);
        $this->assertSame('Makan & Minum', $context->keywordAliases['kuliner']);
    }

    public function test_ai_context_has_temporal_fields(): void
    {
        $snapshot = $this->makeSnapshot();

        $context = $this->aiBuilder->build($snapshot);

        $this->assertSame(now()->toDateString(), $context->today);
        $this->assertSame('Asia/Jakarta', $context->timezone);
        $this->assertSame('id', $context->locale);
    }

    public function test_ai_context_excludes_system_wallets(): void
    {
        $snapshot = $this->makeSnapshot([
            'wallets' => [
                ['id' => 1, 'name' => 'Cash', 'keyword' => 'cash', 'group_type' => 'Personal', 'is_pinned' => false, 'balance' => 50000],
                ['id' => 2, 'name' => 'Merchant System', 'keyword' => '', 'group_type' => 'System', 'is_pinned' => false, 'balance' => 0],
                ['id' => 3, 'name' => 'External System', 'keyword' => '', 'group_type' => 'System', 'is_pinned' => false, 'balance' => 0],
            ],
        ]);

        $context = $this->aiBuilder->build($snapshot);

        $this->assertCount(1, $context->wallets);
        $this->assertSame('Cash', $context->wallets[0]['name']);
    }

    public function test_ai_context_includes_balance_when_option_set(): void
    {
        $snapshot = $this->makeSnapshot([
            'wallets' => [
                ['id' => 1, 'name' => 'Cash', 'keyword' => 'cash', 'group_type' => 'Personal', 'is_pinned' => false, 'balance' => 50000],
                ['id' => 2, 'name' => 'BCA', 'keyword' => 'bca', 'group_type' => 'Personal', 'is_pinned' => true, 'balance' => 100000],
            ],
            'categories' => [
                ['id' => 1, 'category_name' => 'Makanan', 'keyword' => 'makan'],
            ],
        ]);

        $context = $this->aiBuilder->build($snapshot, new ContextOptions(includeWalletBalance: true));

        $this->assertCount(2, $context->wallets);
        $this->assertArrayHasKey('balance', $context->wallets[0]);
        $this->assertArrayHasKey('balance', $context->wallets[1]);
        $this->assertSame(100000, $context->wallets[0]['balance']);
        $this->assertSame(50000, $context->wallets[1]['balance']);
    }

    public function test_ai_context_omits_balance_by_default(): void
    {
        $snapshot = $this->makeSnapshot([
            'wallets' => [
                ['id' => 1, 'name' => 'Cash', 'keyword' => 'cash', 'group_type' => 'Personal', 'is_pinned' => false, 'balance' => 50000],
            ],
            'categories' => [
                ['id' => 1, 'category_name' => 'Makanan', 'keyword' => 'makan'],
            ],
        ]);

        $context = $this->aiBuilder->build($snapshot);

        $this->assertCount(1, $context->wallets);
        $this->assertArrayNotHasKey('balance', $context->wallets[0]);
    }

    private function makeSnapshot(array $overrides = []): ContextSnapshot
    {
        $user = new User;
        $user->timezone = 'Asia/Jakarta';
        $user->locale = 'id';

        $wallets = $this->makeCollection($overrides['wallets'] ?? []);
        $categories = $this->makeCollection($overrides['categories'] ?? []);

        return new ContextSnapshot(
            user: $user,
            userInput: $overrides['userInput'] ?? 'test beli bakso 20rb',
            wallets: $wallets,
            categories: $categories,
            activeMemories: $overrides['memories'] ?? [],
        );
    }

    private function makeCollection(array $data): Collection
    {
        $models = array_map(fn (array $attrs) => new class($attrs) extends Model
        {
            protected $guarded = [];

            public function __construct(array $attributes = [])
            {
                parent::__construct($attributes);
                $this->forceFill($attributes);
            }
        }, $data);

        return new Collection($models);
    }
}
