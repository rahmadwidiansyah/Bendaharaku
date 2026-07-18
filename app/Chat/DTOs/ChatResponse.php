<?php

declare(strict_types=1);

namespace App\Chat\DTOs;

use App\Chat\Components\ChatComponentInterface;
use App\Chat\Errors\ErrorDetail;
use App\Enums\ChatIntent;

/**
 * Output utama dari ChatApplicationService.
 *
 * Platform-agnostic — tidak ada Telegram Markdown, HTML, atau
 * format lain di sini. Semua presentasi dilakukan oleh Formatter.
 *
 * components[] — ordered list komponen untuk render (TextComponent,
 *               TransactionCardComponent, ErrorComponent, dll)
 * errors[]     — ErrorDetail terstruktur (bukan string)
 * metadata     — provider, model, confidence, latency, traceId
 *
 * Gunakan named constructors (success, multiSuccess, failure)
 * untuk membuat ChatResponse yang konsisten.
 */
class ChatResponse
{
    /**
     * @param ChatComponentInterface[] $components  Ordered list untuk render
     * @param ErrorDetail[]            $errors      Error terstruktur
     * @param array                    $metadata    Observability data
     */
    public function __construct(
        public readonly bool        $success,
        public readonly ChatIntent  $intent,
        public readonly array       $components = [],
        public readonly array       $errors     = [],
        public readonly array       $metadata   = [],
    ) {}

    // ── Named constructors ────────────────────────────────────────

    /**
     * Single transaction berhasil.
     */
    public static function singleSuccess(
        array $components,
        array $metadata = [],
    ): self {
        return new self(
            success:    true,
            intent:     ChatIntent::SingleTransaction,
            components: $components,
            metadata:   $metadata,
        );
    }

    /**
     * Single transaction disimpan sebagai draft.
     */
    public static function draft(
        array $components,
        array $metadata = [],
    ): self {
        return new self(
            success:    true,   // draft tetap "success" — transaksi tersimpan
            intent:     ChatIntent::Draft,
            components: $components,
            metadata:   $metadata,
        );
    }

    /**
     * Multi-transaction (bisa semua sukses, parsial, atau semua gagal).
     */
    public static function multiResult(
        bool  $hasAnySuccess,
        array $components,
        array $metadata = [],
    ): self {
        return new self(
            success:    $hasAnySuccess,
            intent:     ChatIntent::MultiTransaction,
            components: $components,
            metadata:   $metadata,
        );
    }

    /**
     * Response untuk perintah (/saldo, /help, dll).
     */
    public static function command(
        array $components,
        array $metadata = [],
    ): self {
        return new self(
            success:    true,
            intent:     ChatIntent::Command,
            components: $components,
            metadata:   $metadata,
        );
    }

    /**
     * Error — AI gagal, konfigurasi salah, dll.
     *
     * @param ErrorDetail[] $errors
     */
    public static function failure(
        array $errors,
        array $components = [],
        array $metadata   = [],
    ): self {
        return new self(
            success:    false,
            intent:     ChatIntent::Error,
            components: $components,
            errors:     $errors,
            metadata:   $metadata,
        );
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function firstError(): ?ErrorDetail
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Ambil metadata value dengan fallback.
     */
    public function meta(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Serialize ke array (untuk JSON API response / logging).
     */
    public function toArray(): array
    {
        return [
            'success'    => $this->success,
            'intent'     => $this->intent->value,
            'components' => array_map(fn (ChatComponentInterface $c) => $c->toArray(), $this->components),
            'errors'     => array_map(fn (ErrorDetail $e) => $e->toArray(), $this->errors),
            'metadata'   => $this->metadata,
        ];
    }
}
