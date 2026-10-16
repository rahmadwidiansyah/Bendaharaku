<?php

declare(strict_types=1);

namespace App\Services\AI\Routing;

use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiTokenLimitException;
use Illuminate\Http\Client\ConnectionException;

/**
 * Klasifikasi error terpusat (port prinsip GAEClip classify_action).
 * Membedakan error MODEL vs AKUN agar fallback tidak membabi-buta.
 */
class AiErrorClassifier
{
    public const CAT_MODEL_NOT_FOUND = 'MODEL_NOT_FOUND';

    public const CAT_AUTH = 'AUTH';

    public const CAT_RATE_LIMIT_MODEL = 'RATE_LIMIT_MODEL';

    public const CAT_RATE_LIMIT_ACCOUNT = 'RATE_LIMIT_ACCOUNT';

    public const CAT_QUOTA = 'QUOTA';

    public const CAT_SERVER = 'SERVER';

    public const CAT_TIMEOUT = 'TIMEOUT';

    public const CAT_NETWORK = 'NETWORK';

    public const CAT_INVALID_REQUEST = 'INVALID_REQUEST';

    public const CAT_CONTEXT_LIMIT = 'CONTEXT_LIMIT';

    public const CAT_INVALID_RESPONSE = 'INVALID_RESPONSE';

    public const CAT_UNKNOWN = 'UNKNOWN';

    public const ACTION_RETRY_SAME = 'RETRY_SAME';

    public const ACTION_NEXT_MODEL = 'NEXT_MODEL';

    public const ACTION_NEXT_ACCOUNT = 'NEXT_ACCOUNT';

    public const ACTION_NEXT_PROVIDER = 'NEXT_PROVIDER';

    public const ACTION_PERMANENT = 'PERMANENT';

    /**
     * @return array{category:string,action:string,scope:string,retryable:bool,fatal:bool,retry_after_s:?float,safe_message:string}
     */
    public static function classify(\Throwable $e): array
    {
        $text = strtolower($e->getMessage().' '.get_class($e));
        $status = self::extractStatus($e);

        // Auth: key invalid/revoked → tandai akun, pindah akun.
        if ($status !== null && in_array($status, [401, 403], true)) {
            return self::out(self::CAT_AUTH, self::ACTION_NEXT_ACCOUNT, 'account', false, true, null, 'API key tidak valid atau akses ditolak.');
        }
        if (str_contains($text, 'api key tidak valid') || str_contains($text, 'api_key_invalid') || str_contains($text, 'api key not valid')) {
            return self::out(self::CAT_AUTH, self::ACTION_NEXT_ACCOUNT, 'account', false, true, null, 'API key tidak valid.');
        }

        // Token/context limit: jangan ulang request identik.
        if ($e instanceof AiTokenLimitException) {
            return self::out(self::CAT_CONTEXT_LIMIT, self::ACTION_NEXT_MODEL, 'model', false, false, null, 'Konteks/token melebihi batas.');
        }
        if ($status === 400 && self::looksLikeContextError($text)) {
            return self::out(self::CAT_CONTEXT_LIMIT, self::ACTION_NEXT_MODEL, 'model', false, false, null, 'Konteks/token melebihi batas.');
        }

        // Model tidak ditemukan / metode tak didukung → tandai model, lanjut.
        if ($status === 404 || str_contains($text, 'model_not_found') || str_contains($text, 'not found') || str_contains($text, 'not supported') || str_contains($text, 'no such model')) {
            return self::out(self::CAT_MODEL_NOT_FOUND, self::ACTION_NEXT_MODEL, 'model', false, false, null, 'Model tidak ditemukan atau tidak mendukung metode ini.');
        }

        // Rate limit / kuota.
        if ($e instanceof AiRateLimitException || $status === 429 || str_contains($text, 'rate limit') || str_contains($text, 'resource exhausted') || str_contains($text, '429')) {
            $retryAfter = self::parseRetryAfter($e);
            // Heuristik aman: jika pesan menyebut project/quota/billing → cakupan akun.
            if (str_contains($text, 'quota') || str_contains($text, 'billing') || str_contains($text, 'project') || str_contains($text, 'account')) {
                return self::out(self::CAT_QUOTA, self::ACTION_NEXT_ACCOUNT, 'account', false, false, $retryAfter, 'Kuota akun/project habis.');
            }

            return self::out(self::CAT_RATE_LIMIT_MODEL, self::ACTION_NEXT_MODEL, 'model', false, false, $retryAfter, 'Rate limit model tercapai.');
        }

        // Timeout / server / network → retry terbatas lalu pindah.
        if ($e instanceof AiTimeoutException || $status !== null && in_array($status, [408, 500, 502, 503, 504], true)) {
            $cat = $status !== null && in_array($status, [500, 502, 503, 504], true) ? self::CAT_SERVER : self::CAT_TIMEOUT;
            $retryAfter = self::parseRetryAfter($e);

            return self::out($cat, self::ACTION_RETRY_SAME, 'pair', true, false, $retryAfter, 'Provider sibuk atau timeout.');
        }
        if ($e instanceof ConnectionException || str_contains($text, 'connection') || str_contains($text, 'network') || str_contains($text, 'timed out') || str_contains($text, 'timeout')) {
            return self::out(self::CAT_NETWORK, self::ACTION_RETRY_SAME, 'pair', true, false, null, 'Gangguan jaringan ke provider.');
        }

        // Request invalid dari aplikasi → permanen, jangan habiskan kandidat.
        if ($status === 400 || $e instanceof \InvalidArgumentException) {
            return self::out(self::CAT_INVALID_REQUEST, self::ACTION_PERMANENT, 'request', false, false, null, 'Permintaan tidak valid.');
        }

        // Respons kosong/format invalid → coba model lain (sekali), bukan akun lain.
        if ($e instanceof AiProviderException && (str_contains($text, 'kosong') || str_contains($text, 'decode json') || str_contains($text, 'tidak mengandung format') || str_contains($text, 'tidak valid'))) {
            return self::out(self::CAT_INVALID_RESPONSE, self::ACTION_NEXT_MODEL, 'model', false, false, null, 'Respons provider tidak valid.');
        }

        return self::out(self::CAT_UNKNOWN, self::ACTION_NEXT_MODEL, 'model', false, false, null, 'Kegagalan tidak dikenal.');
    }

    private static function out(string $category, string $action, string $scope, bool $retryable, bool $fatal, ?float $retryAfter, string $safeMessage): array
    {
        return [
            'category' => $category,
            'action' => $action,
            'scope' => $scope,
            'retryable' => $retryable,
            'fatal' => $fatal,
            'retry_after_s' => $retryAfter !== null ? min(max(0.0, $retryAfter), 300.0) : null,
            'safe_message' => $safeMessage,
        ];
    }

    private static function extractStatus(\Throwable $e): ?int
    {
        // AiProviderException membawa "HTTP {code}:" di message; coba parse.
        if (preg_match('/HTTP\s+(\d{3})/i', $e->getMessage(), $m)) {
            return (int) $m[1];
        }
        $code = $e->getCode();
        if (is_int($code) && $code >= 100 && $code <= 599) {
            return $code;
        }

        return null;
    }

    private static function looksLikeContextError(string $lowerText): bool
    {
        foreach (['token', 'context', 'input too long', 'exceeded', 'maximum', 'too large'] as $hint) {
            if (str_contains($lowerText, $hint)) {
                return true;
            }
        }

        return false;
    }

    private static function parseRetryAfter(\Throwable $e): ?float
    {
        $text = $e->getMessage();
        if (preg_match('/retry[_\s-]*after[:\s]+(\d+(?:\.\d+)?)/i', $text, $m)) {
            return (float) $m[1];
        }
        if (preg_match('/try again in (\d+(?:\.\d+)?)\s*s/i', $text, $m)) {
            return (float) $m[1];
        }

        return null;
    }
}
