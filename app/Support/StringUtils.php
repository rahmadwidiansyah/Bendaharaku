<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;

class StringUtils
{
    public static function splitKeywords(?string $keywords): array
    {
        if (blank($keywords)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\s*[,|;]\s*/', $keywords))
        ));
    }

    public static function normalize(?string $text): string
    {
        return mb_strtolower(trim($text ?? ''));
    }

    public static function tokenizeKeywords(?string $keywords): array
    {
        return array_values(array_filter(
            array_map(fn ($t) => mb_strtolower(trim($t)), self::splitKeywords($keywords))
        ));
    }

    public static function matchesKeyword(string $search, ?string $keywordString): bool
    {
        if (blank($keywordString)) {
            return false;
        }

        $normalized = self::normalize($search);
        $tokens = self::tokenizeKeywords($keywordString);

        return in_array($normalized, $tokens, true);
    }

    public static function findByNameOrKeyword(Collection $items, string $search, string $nameAttr = 'name', string $keywordAttr = 'keyword'): mixed
    {
        $normalized = self::normalize($search);

        $match = $items->first(fn ($item) => self::normalize($item->$nameAttr ?? $item[$nameAttr] ?? '') === $normalized);
        if ($match !== null) {
            return $match;
        }

        $match = $items->first(fn ($item) => self::matchesKeyword($search, $item->$keywordAttr ?? $item[$keywordAttr] ?? null));
        if ($match !== null) {
            return $match;
        }

        return null;
    }

    public static function containsKeyword(string $text, ?string $keywordString): bool
    {
        if (blank($keywordString)) {
            return false;
        }

        $lowerText = self::normalize($text);
        $tokens = self::tokenizeKeywords($keywordString);

        foreach ($tokens as $token) {
            if ($token !== '' && str_contains($lowerText, $token)) {
                return true;
            }
        }

        return false;
    }
}
