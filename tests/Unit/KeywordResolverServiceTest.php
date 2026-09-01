<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\UserAiMemory;
use App\Models\Wallet;
use App\Services\AI\Memory\KeywordResolverService;
use App\Services\AI\Memory\MemoryDecayEngine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class KeywordResolverServiceTest extends TestCase
{
    private KeywordResolverService $service;

    private MemoryDecayEngine&Mockery\MockInterface $decayEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->decayEngine = Mockery::mock(MemoryDecayEngine::class);
        $this->service = new KeywordResolverService($this->decayEngine);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_resolve_category_from_builtin_keyword(): void
    {
        $category = $this->makeCategory(1, 'Makan & Minum', 'bakso,mie');
        $categories = new Collection([$category]);

        $result = $this->service->resolveCategory('bakso', $categories, 999);

        self::assertTrue($result->isResolved());
        self::assertSame(1, $result->targetId);
        self::assertSame('Makan & Minum', $result->targetName);
        self::assertSame('builtin_keyword', $result->matchedBy);
    }

    public function test_resolve_category_from_name(): void
    {
        $category = $this->makeCategory(2, 'Transport', null);
        $categories = new Collection([$category]);

        $result = $this->service->resolveCategory('Transport', $categories, 999);

        self::assertTrue($result->isResolved());
        self::assertSame(2, $result->targetId);
        self::assertSame('builtin_keyword', $result->matchedBy);
    }

    public function test_resolve_category_returns_not_found_when_blank(): void
    {
        $result = $this->service->resolveCategory(null, new Collection, 999);

        self::assertFalse($result->isResolved());
        self::assertSame('none', $result->matchedBy);
    }

    public function test_resolve_category_falls_through_to_memory(): void
    {
        $categories = new Collection;

        $memory = $this->makeMemory(10, 'bakso', 5, null, 3.0);
        $memoryCategory = $this->makeCategory(5, 'Makan & Minum', null);
        $memory->setRelation('category', $memoryCategory);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(new Collection([$memory]));

        $this->decayEngine->shouldReceive('calculateDecayedWeight')
            ->once()
            ->andReturn(2.5);

        $result = $this->service->resolveCategory('bakso', $categories, 1);

        self::assertTrue($result->isResolved());
        self::assertSame(5, $result->targetId);
        self::assertSame('user_memory', $result->matchedBy);
        self::assertSame('bakso', $result->matchedKeyword);
    }

    public function test_resolve_category_memory_skipped_when_decayed(): void
    {
        $categories = new Collection;

        $memory = $this->makeMemory(10, 'bakso', 5, null, 0.05);
        $memory->setRelation('category', $this->makeCategory(5, 'Makan', null));

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(new Collection([$memory]));

        $this->decayEngine->shouldReceive('calculateDecayedWeight')
            ->once()
            ->andReturn(0.05);

        $result = $this->service->resolveCategory('bakso', $categories, 1);

        self::assertFalse($result->isResolved());
        self::assertSame('none', $result->matchedBy);
    }

    public function test_resolve_wallet_from_builtin_keyword(): void
    {
        $wallet = $this->makeWallet(3, 'BCA', 'bca,mandiri');
        $wallets = new Collection([$wallet]);

        $result = $this->service->resolveWallet('bca', $wallets, 999);

        self::assertTrue($result->isResolved());
        self::assertSame(3, $result->targetId);
        self::assertSame('BCA', $result->targetName);
        self::assertSame('builtin_keyword', $result->matchedBy);
    }

    public function test_resolve_wallet_falls_through_to_memory(): void
    {
        $wallets = new Collection;

        $memory = $this->makeMemory(11, 'cash', null, 7, 2.0);
        $memoryWallet = $this->makeWallet(7, 'Dompet Tunai', null);
        $memory->setRelation('wallet', $memoryWallet);
        $memory->setRelation('category', null);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(new Collection([$memory]));

        $this->decayEngine->shouldReceive('calculateDecayedWeight')
            ->once()
            ->andReturn(1.5);

        $result = $this->service->resolveWallet('cash', $wallets, 1);

        self::assertTrue($result->isResolved());
        self::assertSame(7, $result->targetId);
        self::assertSame('Dompet Tunai', $result->targetName);
        self::assertSame('user_memory', $result->matchedBy);
    }

    public function test_resolve_wallet_returns_not_found(): void
    {
        $wallets = new Collection;

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(new Collection);

        $result = $this->service->resolveWallet('unknown', $wallets, 1);

        self::assertFalse($result->isResolved());
        self::assertSame('none', $result->matchedBy);
    }

    public function test_builtin_takes_priority_over_memory(): void
    {
        $category = $this->makeCategory(1, 'Makan & Minum', 'bakso');
        $categories = new Collection([$category]);

        $result = $this->service->resolveCategory('bakso', $categories, 1);

        self::assertSame('builtin_keyword', $result->matchedBy);
        self::assertSame(1, $result->targetId);
    }

    private function makeCategory(int $id, string $name, ?string $keyword): Category
    {
        $category = new Category;
        $category->id = $id;
        $category->category_name = $name;
        $category->keyword = $keyword;
        $category->exists = true;

        return $category;
    }

    private function makeWallet(int $id, string $name, ?string $keyword): Wallet
    {
        $wallet = new Wallet;
        $wallet->id = $id;
        $wallet->name = $name;
        $wallet->keyword = $keyword;
        $wallet->exists = true;

        return $wallet;
    }

    private function makeMemory(int $id, string $keyword, ?int $categoryId, ?int $walletId, float $weight): UserAiMemory
    {
        $memory = new UserAiMemory;
        $memory->id = $id;
        $memory->keyword_pattern = $keyword;
        $memory->category_id = $categoryId;
        $memory->wallet_id = $walletId;
        $memory->weight = $weight;
        $memory->last_applied_at = now()->subDay();
        $memory->created_at = now()->subWeek();
        $memory->exists = true;

        return $memory;
    }
}
