<?php

declare(strict_types=1);

namespace App\Services\AI\Memory;

readonly class MemoryKeywordExtractor
{
    private array $stopwords;

    private int $minLength;

    public function __construct()
    {
        $this->stopwords = config('memory.stopwords', []);
        $this->minLength = (int) config('memory.min_keyword_length', 3);
    }

    public function extract(string $subject): array
    {
        $raw = trim($subject);
        $normalized = $this->normalize($raw);
        $keyword = $this->extractKeyword($normalized);

        return [
            'raw' => $raw,
            'normalized' => $normalized,
            'keyword' => $keyword,
            'keywords' => $this->extractAllKeywords($normalized),
        ];
    }

    public function extractAllKeywords(string $normalized): array
    {
        $tokens = explode(' ', $normalized);
        $tokens = array_filter($tokens, fn (string $t) => ! in_array($t, $this->stopwords, true));
        $tokens = array_filter($tokens, fn (string $t) => mb_strlen($t) >= $this->minLength);

        $tokens = array_values($tokens);
        if (empty($tokens)) {
            return [];
        }

        $ngrams = [];
        $count = count($tokens);

        if ($count >= 2) {
            $ngrams[] = $tokens[0].' '.$tokens[1];
        }

        return array_merge($tokens, $ngrams);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function extractKeyword(string $normalized): string
    {
        $tokens = $this->extractAllKeywords($normalized);

        if (empty($tokens)) {
            return $normalized;
        }

        return $tokens[0];
    }
}
