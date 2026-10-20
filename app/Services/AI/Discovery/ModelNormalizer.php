<?php

declare(strict_types=1);

namespace App\Services\AI\Discovery;

use App\Enums\AiProvider;

/**
 * Normalisasi metadata model ala GAEClip ai_models.py — capabilities first,
 * nama hanya fallback. Tidak pernah mengarang model/free-tier.
 */
class ModelNormalizer
{
    public const CAT_TEXT = 'text_generation';

    public const CAT_MULTIMODAL = 'multimodal';

    public const CAT_EMBEDDING = 'embedding';

    public const CAT_IMAGE = 'image_generation';

    public const CAT_TTS = 'text_to_speech';

    public const CAT_LIVE = 'live';

    public const CAT_OTHER = 'other';

    public const ELIGIBLE = ['text_generation', 'multimodal'];

    /**
     * @return array{id:string,display_name:string,supports_generate:bool,family:string,generation:string,variant:string,release_channel:string,is_preview:bool,is_experimental:bool,is_latest_alias:bool,category:string,free_tier:string,free_tier_source:string,eligible:bool,input_token_limit:?int,output_token_limit:?int,version:string,description:string}
     */
    public static function normalizeGemini(array $raw): array
    {
        $name = (string) ($raw['name'] ?? '');
        $modelId = str_contains($name, '/') ? substr($name, (int) strpos($name, '/') + 1) : $name;
        $modelId = trim($modelId);
        $methods = array_map('strval', (array) ($raw['supportedGenerationMethods'] ?? []));
        $supportsGc = false;
        foreach ($methods as $m) {
            if (in_array(strtolower($m), ['generatecontent', 'generate_content'], true)) {
                $supportsGc = true;
                break;
            }
        }
        [$channel, $isPreview, $isExperimental, $isLatest] = self::releaseChannel($modelId);
        $category = self::classifyCategory($modelId, $methods);
        $variant = self::variant($modelId);
        $generation = self::parseGeneration($modelId);
        $interactionsOnly = self::requiresInteractionsApi($modelId);

        $freeTier = self::freeTierStatus($variant, $channel);
        $eligible = $supportsGc
            && ! $interactionsOnly
            && in_array($category, self::ELIGIBLE, true)
            && $modelId !== '';

        $description = mb_substr((string) ($raw['description'] ?? ''), 0, 500);
        if ($interactionsOnly) {
            $description = trim($description.' [requires Interactions API]');
        }

        return [
            'id' => $modelId,
            'display_name' => (string) ($raw['displayName'] ?? $modelId),
            'supports_generate' => $supportsGc && ! $interactionsOnly,
            'family' => str_starts_with(strtolower($modelId), 'gemini') ? 'gemini' : 'other',
            'generation' => $generation,
            'variant' => $variant,
            'release_channel' => $channel,
            'is_preview' => $isPreview,
            'is_experimental' => $isExperimental,
            'is_latest_alias' => $isLatest,
            'category' => $interactionsOnly ? self::CAT_OTHER : $category,
            'free_tier' => $freeTier,
            'free_tier_source' => 'local_policy',
            'eligible' => $eligible,
            'input_token_limit' => self::toIntOrNull($raw['inputTokenLimit'] ?? null),
            'output_token_limit' => self::toIntOrNull($raw['outputTokenLimit'] ?? null),
            'version' => (string) ($raw['version'] ?? $generation),
            'description' => $description,
        ];
    }

    /**
     * Model yang terdaftar di Models API tetapi hanya menerima Interactions API,
     * bukan endpoint generateContent yang dipakai adapter chat.
     */
    public static function requiresInteractionsApi(string $modelId): bool
    {
        return str_contains(strtolower($modelId), 'antigravity');
    }

    /** Normalisasi generik untuk OpenAI-compatible /v1/models (id + created). */
    public static function normalizeOpenAiCompatible(string $modelId, AiProvider $provider, ?string $displayName = null): array
    {
        $modelId = trim($modelId);
        $lower = strtolower($modelId);
        // Tanpa metadata capabilities, tandai supports_generate=true konservatif
        // tapi eligible hanya jika nama tampak seperti chat model (hindari embedding/tts/image).
        $unsupportedHints = ['embed', 'imagen', 'tts', 'whisper', 'dall-e', 'live'];
        $eligible = $modelId !== '';
        foreach ($unsupportedHints as $hint) {
            if (str_contains($lower, $hint)) {
                $eligible = false;
                break;
            }
        }
        $variant = self::variant($modelId);
        $generation = self::parseGeneration($modelId);
        [$channel] = self::releaseChannel($modelId);

        return [
            'id' => $modelId,
            'display_name' => $displayName ?? $modelId,
            'supports_generate' => $eligible,
            'family' => $provider->value,
            'generation' => $generation,
            'variant' => $variant,
            'release_channel' => $channel,
            'is_preview' => str_contains($lower, 'preview'),
            'is_experimental' => str_contains($lower, 'experimental') || str_contains($lower, '-exp'),
            'is_latest_alias' => str_ends_with($lower, '-latest'),
            'category' => $eligible ? self::CAT_TEXT : self::CAT_OTHER,
            // Untuk non-Gemini: jangan klaim free tanpa bukti → unknown.
            'free_tier' => 'unknown',
            'free_tier_source' => 'unknown_no_quota_api',
            'eligible' => $eligible,
            'input_token_limit' => null,
            'output_token_limit' => null,
            'version' => $generation,
            'description' => '',
        ];
    }

    public static function parseGeneration(string $modelId): string
    {
        if (preg_match('/gemini-(\d+(?:\.\d+)?)/i', $modelId, $m)) {
            return $m[1];
        }

        return '';
    }

    public static function variant(string $modelId): string
    {
        $lower = strtolower($modelId);
        if (str_contains($lower, 'flash-lite') || str_contains($lower, 'flash_light') || str_ends_with($lower, '-lite')) {
            return 'flash-lite';
        }
        if (str_contains($lower, 'flash')) {
            return 'flash';
        }
        if (str_contains($lower, 'pro')) {
            return 'pro';
        }
        if (str_contains($lower, 'ultra')) {
            return 'ultra';
        }

        return 'other';
    }

    /** @return array{string,bool,bool,bool} */
    public static function releaseChannel(string $modelId): array
    {
        $lower = strtolower($modelId);
        $isLatest = str_ends_with($lower, '-latest') || $lower === 'latest' || str_contains($lower, '-latest-');
        $isPreview = str_contains($lower, 'preview');
        $isExperimental = str_contains($lower, 'experimental') || str_contains($lower, '-exp');
        if ($isExperimental) {
            return ['experimental', $isPreview, true, $isLatest];
        }
        if ($isPreview) {
            return ['preview', true, false, $isLatest];
        }
        if ($isLatest) {
            return ['latest_alias', false, false, true];
        }
        if (preg_match('/gemini-\d+(\.\d+)?-/i', $lower)) {
            return ['stable', false, false, false];
        }

        return ['unknown', false, false, false];
    }

    /** @param list<string> $methods */
    public static function classifyCategory(string $modelId, array $methods): string
    {
        $normalized = array_map(fn ($m) => strtolower(str_replace('_', '', (string) $m)), $methods);
        $lower = strtolower($modelId);
        $hasEmbed = false;
        foreach ($normalized as $m) {
            if (str_contains($m, 'embed')) {
                $hasEmbed = true;
                break;
            }
        }
        if ($hasEmbed || str_contains($lower, 'embedding')) {
            return self::CAT_EMBEDDING;
        }
        if (str_contains($lower, 'imagen') || (str_contains($lower, 'image') && ! in_array('generatecontent', $normalized, true))) {
            return self::CAT_IMAGE;
        }
        if (str_contains($lower, 'tts') || str_contains($lower, 'text-to-speech') || (str_contains($lower, 'speech') && ! in_array('generatecontent', $normalized, true))) {
            return self::CAT_TTS;
        }
        if (str_contains($lower, 'live') && ! in_array('generatecontent', $normalized, true)) {
            return self::CAT_LIVE;
        }
        if (in_array('generatecontent', $normalized, true)) {
            return str_starts_with($lower, 'gemini') ? self::CAT_MULTIMODAL : self::CAT_TEXT;
        }
        if ($methods === []) {
            if (str_contains($lower, 'embed') || str_contains($lower, 'imagen') || str_contains($lower, 'tts') || str_contains($lower, 'live')) {
                return self::CAT_OTHER;
            }
            if (str_contains($lower, 'flash') || str_contains($lower, 'pro') || str_contains($lower, 'ultra') || str_contains($lower, 'nano') || str_contains($lower, 'gemini') || str_contains($lower, 'gpt') || str_contains($lower, 'Muse')) {
                return self::CAT_TEXT;
            }

            return self::CAT_OTHER;
        }

        return self::CAT_OTHER;
    }

    public static function freeTierStatus(string $variant, string $channel): string
    {
        if (in_array($channel, ['preview', 'experimental'], true)) {
            return 'unknown';
        }
        if (in_array($variant, ['flash', 'flash-lite'], true)) {
            return 'free';
        }
        if (in_array($variant, ['pro', 'ultra'], true)) {
            return 'paid';
        }

        return 'unknown';
    }

    /** @return -1000 untuk tidak layak, else skor internal (tidak ditampilkan ke user). */
    public static function internalScore(array $info, bool $freeOnly): float
    {
        if (! ($info['eligible'] ?? false)) {
            return -1000.0;
        }
        if ($freeOnly && ($info['free_tier'] ?? 'unknown') !== 'free') {
            return -1000.0;
        }
        $score = 0.0;
        $channel = (string) ($info['release_channel'] ?? 'unknown');
        $score += match ($channel) {
            'stable' => 100.0,
            'latest_alias' => 40.0,
            'preview' => -20.0,
            'experimental' => -50.0,
            default => 0.0,
        };
        $score += match ((string) ($info['variant'] ?? 'other')) {
            'flash' => 80.0,
            'flash-lite' => 70.0,
            'pro' => 60.0,
            default => 40.0,
        };
        $gen = self::generationTuple((string) ($info['generation'] ?? ''));
        if ($gen !== []) {
            $score += (($gen[0] * 10) + ($gen[1] ?? 0)) * 0.5;
        }

        return $score;
    }

    /** @return list<int> */
    public static function generationTuple(string $generation): array
    {
        $out = [];
        foreach (explode('.', $generation) as $piece) {
            if (! is_numeric($piece)) {
                break;
            }
            $out[] = (int) $piece;
        }

        return $out;
    }

    private static function toIntOrNull(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (int) $v;
        }

        return null;
    }
}
