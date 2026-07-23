<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AI\Scoring\Matchers\MemoryMatchService;
use Tests\TestCase;

class MemoryMatchServiceTest extends TestCase
{
    private MemoryMatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MemoryMatchService;
    }

    public function test_returns_zero_when_no_memories(): void
    {
        $score = $this->service->calculateScore('makan bakso', []);
        $this->assertSame(0.0, $score);
    }

    public function test_returns_zero_when_keyword_does_not_match(): void
    {
        $memories = [
            ['keyword' => 'bca', 'effective_weight' => 5.0],
        ];

        $score = $this->service->calculateScore('makan bakso', $memories);
        $this->assertSame(0.0, $score);
    }

    public function test_returns_score_based_on_effective_weight(): void
    {
        $memories = [
            ['keyword' => 'bakso', 'effective_weight' => 5.0],
        ];

        $score = $this->service->calculateScore('makan bakso', $memories);
        $this->assertSame(0.5, $score); // 5.0 / 10.0
    }

    public function test_caps_score_at_max_effective_weight(): void
    {
        $memories = [
            ['keyword' => 'bakso', 'effective_weight' => 20.0],
        ];

        $score = $this->service->calculateScore('makan bakso', $memories);
        $this->assertSame(1.0, $score);
    }

    public function test_uses_highest_weight_when_multiple_memories_match(): void
    {
        $memories = [
            ['keyword' => 'bakso', 'effective_weight' => 3.0],
            ['keyword' => 'bca', 'effective_weight' => 8.0],
        ];

        $score = $this->service->calculateScore('makan bakso bca', $memories);
        $this->assertSame(0.8, $score); // 8.0 / 10.0
    }
}
