<?php

declare(strict_types=1);

namespace App\Chat\Errors;

use App\Enums\ChatErrorSeverity;

/**
 * Representasi terstruktur satu error dalam Chat Engine.
 *
 * Business logic melempar ErrorDetail — BUKAN string.
 * Formatter yang memanggil trans($messageKey, $params) sesuai locale.
 *
 * Tidak ada pesan final di sini — hanya metadata.
 *
 * code        — kode mesin: 'WALLET_NOT_FOUND', 'INVALID_AMOUNT', dll
 * messageKey  — translation key: 'chat.wallet.not_found'
 * params      — substitusi: ['name' => 'spay']
 * field       — field yang menyebabkan error (opsional, untuk form validation)
 * rawValue    — nilai mentah dari user yang menyebabkan error
 * suggestion  — translation key untuk saran perbaikan (opsional)
 * severity    — level keparahan
 * recoverable — apakah user bisa retry sendiri atau perlu bantuan
 */
readonly class ErrorDetail
{
    public function __construct(
        public string            $code,
        public string            $messageKey,
        public array             $params      = [],
        public ?string           $field       = null,
        public ?string           $rawValue    = null,
        public ?string           $suggestion  = null,
        public ChatErrorSeverity $severity    = ChatErrorSeverity::Error,
        public bool              $recoverable = true,
    ) {}

    // ── Named constructors untuk kasus umum ──────────────────────

    public static function walletNotFound(string $name): self
    {
        return new self(
            code:        'WALLET_NOT_FOUND',
            messageKey:  'chat.wallet.not_found',
            params:      ['name' => $name],
            rawValue:    $name,
            suggestion:  'chat.suggestion.add_wallet',
            severity:    ChatErrorSeverity::Error,
            recoverable: true,
        );
    }

    public static function categoryNotFound(string $name): self
    {
        return new self(
            code:        'CATEGORY_NOT_FOUND',
            messageKey:  'chat.category.not_found',
            params:      ['name' => $name],
            rawValue:    $name,
            suggestion:  'chat.suggestion.add_category',
            severity:    ChatErrorSeverity::Error,
            recoverable: true,
        );
    }

    public static function invalidAmount(): self
    {
        return new self(
            code:        'INVALID_AMOUNT',
            messageKey:  'chat.validation.invalid_amount',
            severity:    ChatErrorSeverity::Error,
            recoverable: true,
        );
    }

    public static function sameWallet(): self
    {
        return new self(
            code:        'SAME_WALLET',
            messageKey:  'chat.validation.same_wallet',
            severity:    ChatErrorSeverity::Error,
            recoverable: true,
        );
    }

    public static function aiNotConfigured(): self
    {
        return new self(
            code:        'AI_NOT_CONFIGURED',
            messageKey:  'chat.ai.not_configured',
            severity:    ChatErrorSeverity::Critical,
            recoverable: false,
        );
    }

    public static function aiRateLimit(string $provider): self
    {
        return new self(
            code:        'AI_RATE_LIMIT',
            messageKey:  'chat.ai.rate_limit',
            params:      ['provider' => $provider],
            severity:    ChatErrorSeverity::Warning,
            recoverable: true,
        );
    }

    public static function aiTimeout(string $provider): self
    {
        return new self(
            code:        'AI_TIMEOUT',
            messageKey:  'chat.ai.timeout',
            params:      ['provider' => $provider],
            severity:    ChatErrorSeverity::Warning,
            recoverable: true,
        );
    }

    public static function aiProviderError(string $provider, string $message): self
    {
        return new self(
            code:        'AI_PROVIDER_ERROR',
            messageKey:  'chat.ai.provider_error',
            params:      ['provider' => $provider, 'error' => $message],
            severity:    ChatErrorSeverity::Error,
            recoverable: false,
        );
    }

    public static function systemError(): self
    {
        return new self(
            code:        'SYSTEM_ERROR',
            messageKey:  'chat.error.system',
            severity:    ChatErrorSeverity::Critical,
            recoverable: false,
        );
    }

    // ── Serialization ─────────────────────────────────────────────

    public function toArray(): array
    {
        return [
            'code'        => $this->code,
            'message_key' => $this->messageKey,
            'params'      => $this->params,
            'field'       => $this->field,
            'raw_value'   => $this->rawValue,
            'suggestion'  => $this->suggestion,
            'severity'    => $this->severity->value,
            'recoverable' => $this->recoverable,
        ];
    }
}
