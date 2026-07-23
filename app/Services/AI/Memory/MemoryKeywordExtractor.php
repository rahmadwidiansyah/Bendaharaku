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

    /**
     * Mengekstrak keyword canonical dari subject mentah.
     *
     * Pipeline:
     *   1. lowercase
     *   2. hapus karakter non-alfanumerik (kecuali spasi)
     *   3. tokenize
     *   4. hapus stopword
     *   5. filter panjang minimum
     *   6. ambil token pertama sebagai keyword utama
     *
     * @return array{raw: string, normalized: string, keyword: string}
     */
    public function extract(string $subject): array
    {
        $raw = trim($subject);

        $normalized = $this->normalize($raw);

        $keyword = $this->extractKeyword($normalized);

        return [
            'raw' => $raw,
            'normalized' => $normalized,
            'keyword' => $keyword,
        ];
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
        $tokens = explode(' ', $normalized);
        $tokens = array_filter($tokens, fn (string $t) => ! in_array($t, $this->stopwords, true));
        $tokens = array_filter($tokens, fn (string $t) => mb_strlen($t) >= $this->minLength);

        $tokens = array_values($tokens);

        if (empty($tokens)) {
            return $normalized;
        }

        return $tokens[0];
    }
}
