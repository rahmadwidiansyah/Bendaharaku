<?php

declare(strict_types=1);

namespace App\Chat\DTOs;

use App\Models\User;
use Carbon\Carbon;

/**
 * Satu pesan masuk dari user — platform-agnostic.
 *
 * ChatRequest dibuat oleh Adapter setelah menerima payload
 * dari platform (Telegram Update, HTTP POST, WebSocket, dll).
 *
 * Semua parameter platform-specific (chat_id, message_id, dll)
 * sudah di-abstract ke dalam ChatContext.
 *
 * ChatApplicationService hanya menerima ChatRequest —
 * tidak tahu dari mana pesan berasal.
 */
readonly class ChatRequest
{
    public function __construct(
        /** Teks mentah dari user, belum di-sanitasi */
        public string $rawMessage,

        /** User Eloquent model (sudah di-resolve oleh Adapter) */
        public User $user,

        /** Metadata platform dan session */
        public ChatContext $context,

        /** Waktu pesan diterima (bisa di-override untuk timezone conversion) */
        public Carbon $timestamp,

        /** Attachment jika ada (future: gambar struk, voice note, dll) */
        public array $attachments = [],
    ) {}

    /**
     * Factory cepat tanpa perlu buat Carbon manual.
     */
    public static function make(
        string $rawMessage,
        User $user,
        ChatContext $context,
        array $attachments = [],
    ): self {
        return new self(
            rawMessage: $rawMessage,
            user: $user,
            context: $context,
            timestamp: Carbon::now($context->timezone),
            attachments: $attachments,
        );
    }

    /**
     * Teks yang sudah di-trim (shortcut untuk formatter/service).
     */
    public function normalizedMessage(): string
    {
        return trim($this->rawMessage);
    }
}
