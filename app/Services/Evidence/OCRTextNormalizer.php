<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use Illuminate\Support\Facades\Log;

/**
 * OCRTextNormalizer — Membersihkan hasil OCR sebelum dipakai Classifier/Parser.
 *
 * Normalisasi:
 * - Karakter OCR salah dalam konteks angka (O→0, l→1, I→1, S→5, B→8)
 * - Currency aliases (Rpl, Rp., RP, IDR → Rp)
 * - Wallet aliases (Sea BanK → SeaBank, OV0 → OVO, dll)
 * - Whitespace cleanup
 * - Reference number cleanup
 * - Noise removal
 * - Unicode normalization
 */
class OCRTextNormalizer
{
    private array $currencyAliases;

    private array $walletAliases;

    private array $numberCharMap;

    private array $noisePatterns;

    private array $whitespaceRules;

    private array $referenceCleanup;

    private array $unicodeMap;

    private bool $debug;

    public function __construct()
    {
        $this->currencyAliases = config('ocr_normalizer.currency_aliases', []);
        $this->walletAliases = config('ocr_normalizer.wallet_aliases', []);
        $this->numberCharMap = config('ocr_normalizer.number_char_map', []);
        $this->noisePatterns = config('ocr_normalizer.noise_patterns', []);
        $this->whitespaceRules = config('ocr_normalizer.whitespace', []);
        $this->referenceCleanup = config('ocr_normalizer.reference_cleanup', []);
        $this->unicodeMap = config('ocr_normalizer.unicode_map', []);
        $this->debug = config('ocr_normalizer.debug', false);
    }

    /**
     * Normalize raw OCR text.
     *
     * @return array{normalized: string, changes: int}
     */
    public function normalize(string $text): array
    {
        $start = microtime(true);
        $originalLength = strlen($text);
        $totalChanges = 0;

        try {
            Log::channel(config('evidence.log_channel', 'evidence'))->info('Normalization started', [
                'original_length' => $originalLength,
            ]);
        } catch (\Throwable) {
        }

        // 1. Unicode normalization
        [$text, $unicodeChanges] = $this->normalizeUnicode($text);
        $totalChanges += $unicodeChanges;

        // 2. Noise removal
        [$text, $noiseChanges] = $this->removeNoise($text);
        $totalChanges += $noiseChanges;

        // 3. Currency normalization
        [$text, $currencyChanges] = $this->normalizeCurrency($text);
        $totalChanges += $currencyChanges;

        // 4. Wallet name normalization
        [$text, $walletChanges] = $this->normalizeWalletNames($text);
        $totalChanges += $walletChanges;

        // 5. Number context character correction
        [$text, $numberChanges] = $this->normalizeNumberContext($text);
        $totalChanges += $numberChanges;

        // 6. Whitespace cleanup
        [$text, $wsChanges] = $this->normalizeWhitespace($text);
        $totalChanges += $wsChanges;

        // 7. Reference number cleanup (if standalone reference lines)
        [$text, $refChanges] = $this->normalizeReferences($text);
        $totalChanges += $refChanges;

        $duration = (int) ((microtime(true) - $start) * 1000);

        try {
            Log::channel(config('evidence.log_channel', 'evidence'))->info('Normalization finished', [
                'original_length' => $originalLength,
                'normalized_length' => strlen($text),
                'total_changes' => $totalChanges,
                'duration_ms' => $duration,
            ]);
        } catch (\Throwable) {
        }

        return [
            'normalized' => $text,
            'changes' => $totalChanges,
            'duration_ms' => $duration,
        ];
    }

    /**
     * 1. Unicode normalization — replace karakter Unicode sering muncul dari OCR.
     */
    private function normalizeUnicode(string $text): array
    {
        $changes = 0;
        foreach ($this->unicodeMap as $from => $to) {
            $count = substr_count($text, $from);
            if ($count > 0) {
                $text = str_replace($from, $to, $text);
                $changes += $count;
            }
        }

        // NFKC normalization (decompose + recompose)
        if (function_exists('mb_normalize_encoding')) {
            $normalized = mb_normalize_encoding($text, 'UTF-8', 'UTF-8');
            if ($normalized !== false) {
                $text = $normalized;
            }
        }

        return [$text, $changes];
    }

    /**
     * 2. Noise removal — hapus karakter acak OCR (---, ===, ***).
     */
    private function removeNoise(string $text): array
    {
        $changes = 0;
        foreach ($this->noisePatterns as $pattern) {
            $before = $text;
            $text = preg_replace($pattern, '', $text);
            if ($text !== $before) {
                $changes += preg_match_all($pattern, $before);
            }
        }

        return [$text, $changes];
    }

    /**
     * 3. Currency normalization — Rpl, Rp., RP, IDR → Rp.
     */
    private function normalizeCurrency(string $text): array
    {
        $changes = 0;

        foreach ($this->currencyAliases as $canonical => $variants) {
            foreach ($variants as $variant) {
                // Case-insensitive replacement
                $pattern = '/'.preg_quote($variant, '/').'/i';
                $before = $text;
                $text = preg_replace($pattern, $canonical, $text);
                if ($text !== $before) {
                    $changes += 1;
                }
            }
        }

        return [$text, $changes];
    }

    /**
     * 4. Wallet name normalization — Sea BanK → SeaBank, OV0 → OVO.
     */
    private function normalizeWalletNames(string $text): array
    {
        $changes = 0;

        foreach ($this->walletAliases as $canonical => $variants) {
            foreach ($variants as $variant) {
                if ($variant === $canonical) {
                    continue;
                }
                // Case-sensitive by default (wallet names matter)
                $before = $text;
                $text = str_replace($variant, $canonical, $text);
                if ($text !== $before) {
                    $changes += 1;
                }
            }
        }

        return [$text, $changes];
    }

    /**
     * 5. Number context character correction.
     *
     * Hanya mengganti karakter jika berada di konteks angka:
     * - Di sebelah digit atau karakter numerik lainnya
     * - Di dalam string yang mengandung digit atau karakter numerik suspicious
     */
    private function normalizeNumberContext(string $text): array
    {
        $changes = 0;
        $lines = explode("\n", $text);

        $suspiciousChars = array_keys($this->numberCharMap);
        $suspiciousClass = preg_quote(implode('', $suspiciousChars), '/');
        $segPattern = '/([\d'.$suspiciousClass.'.]{2,})/';

        foreach ($lines as &$line) {
            if (! preg_match_all($segPattern, $line, $matches)) {
                continue;
            }

            $lineBefore = $line;

            foreach ($matches[0] as $segment) {
                $hasDigit = (bool) preg_match('/\d/', $segment);
                $hasDot = str_contains($segment, '.');

                if (! $hasDigit && ! $hasDot) {
                    continue;
                }

                $normalized = $segment;
                $prev = $normalized;
                do {
                    $prev = $normalized;
                    foreach ($this->numberCharMap as $wrong => $correct) {
                        $normalized = str_replace($wrong, $correct, $normalized);
                    }
                } while ($normalized !== $prev);

                if ($normalized !== $segment) {
                    $line = substr_replace($line, $normalized, strpos($line, $segment), strlen($segment));
                }
            }

            if ($line !== $lineBefore) {
                $changes++;
            }
        }
        unset($line);

        $text = implode("\n", $lines);

        return [$text, $changes];
    }

    /**
     * 6. Whitespace cleanup.
     */
    private function normalizeWhitespace(string $text): array
    {
        $changes = 0;

        foreach ($this->whitespaceRules as $pattern => $replacement) {
            $before = $text;
            $text = preg_replace($pattern, $replacement, $text);
            if ($text !== $before) {
                $changes += 1;
            }
        }

        // Trim overall
        $text = trim($text);

        return [$text, $changes];
    }

    /**
     * 7. Reference number cleanup — hapus spasi berlebihan pada baris referensi.
     */
    private function normalizeReferences(string $text): array
    {
        $changes = 0;

        $lines = explode("\n", $text);

        foreach ($lines as &$line) {
            $trimmed = trim($line);

            if (strlen($trimmed) < 3) {
                continue;
            }

            // Strip common reference prefixes (REF:, NO:, etc.)
            $hasPrefix = (bool) preg_match('/^(REF|NO|NUM|ID|KODE|CODE)[:\s]+/i', $trimmed, $prefixMatch);
            $stripped = preg_replace('/^(REF|NO|NUM|ID|KODE|CODE)[:\s]+/i', '', $trimmed, 1);

            if (preg_match('/^[A-Za-z0-9\s]+$/', $stripped)) {
                // Only collapse spaces if it looks like a reference code:
                // - has a known prefix, OR
                // - contains digits mixed with letters (e.g. ABC 123 456)
                $isReference = $hasPrefix
                    || (preg_match('/\d/', $stripped) && preg_match('/[A-Za-z]/', $stripped));

                if ($isReference) {
                    $cleaned = preg_replace('/\s+/', '', $stripped);
                    if (strlen($cleaned) >= 3) {
                        $line = $cleaned;
                        $changes += 1;
                    }
                }
            }
        }
        unset($line);

        $text = implode("\n", $lines);

        return [$text, $changes];
    }
}
