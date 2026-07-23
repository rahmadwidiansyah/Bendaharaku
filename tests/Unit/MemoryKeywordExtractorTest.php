<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AI\Memory\MemoryKeywordExtractor;
use Tests\TestCase;

class MemoryKeywordExtractorTest extends TestCase
{
    private MemoryKeywordExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new MemoryKeywordExtractor;
    }

    public function test_extracts_keyword_from_simple_subject(): void
    {
        $result = $this->extractor->extract('Bakso');

        $this->assertSame('Bakso', $result['raw']);
        $this->assertSame('bakso', $result['normalized']);
        $this->assertSame('bakso', $result['keyword']);
    }

    public function test_removes_stopwords_and_uses_first_meaningful_token(): void
    {
        $result = $this->extractor->extract('Bakso Pak Kumis');

        $this->assertSame('bakso', $result['keyword']);
        $this->assertSame('bakso pak kumis', $result['normalized']);
    }

    public function test_handles_full_sentence(): void
    {
        $result = $this->extractor->extract('Aku beli bakso pak kumis');

        $this->assertSame('beli', $result['keyword']);
        $this->assertSame('aku beli bakso pak kumis', $result['normalized']);
    }

    public function test_handles_merchant_name(): void
    {
        $result = $this->extractor->extract('BCA');

        $this->assertSame('bca', $result['keyword']);
    }

    public function test_returns_normalized_when_no_valid_keyword_found(): void
    {
        $result = $this->extractor->extract('di');

        $this->assertSame('di', $result['keyword']);
    }

    public function test_removes_non_alphanumeric_characters(): void
    {
        $result = $this->extractor->extract('Bakso@#Pak!');

        $this->assertSame('bakso pak', $result['normalized']);
        $this->assertSame('bakso', $result['keyword']);
    }

    public function test_extract_bca_transfer(): void
    {
        $result = $this->extractor->extract('Transfer ke BCA');

        $this->assertSame('transfer ke bca', $result['normalized']);
        $this->assertSame('transfer', $result['keyword']);
    }
}
