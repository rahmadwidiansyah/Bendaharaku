<?php

declare(strict_types=1);

namespace App\Chat\DTOs;

use App\Enums\ChatPlatform;
use Illuminate\Support\Str;

/**
 * Metadata platform untuk satu sesi percakapan.
 *
 * ChatContext dibuat oleh Adapter (TelegramAdapter, WebChatAdapter, dll)
 * dan diteruskan ke seluruh layer tanpa modifikasi.
 *
 * Business logic TIDAK boleh menciptakan ChatContext — hanya membacanya.
 *
 * traceId: ULID unik per pesan, digunakan di semua Log::* calls
 * agar seluruh log satu request bisa di-filter sekaligus.
 *
 * Locale resolution order (tertinggi ke terendah prioritas):
 *   1. $user->locale (jika diisi di settings)
 *   2. $platformLocale (dari metadata platform, mis. Telegram language_code)
 *   3. config('app.locale') → 'id'
 */
readonly class ChatContext
{
    public function __construct(
        /** Platform asal pesan: telegram, web, whatsapp, dll */
        public ChatPlatform $platform,

        /** ID percakapan di platform: chat_id Telegram, session_id Web, phone WA */
        public string $conversationId,

        /** Locale yang sudah di-resolve (siap pakai untuk trans()) */
        public string $locale,

        /** IANA timezone user, default 'Asia/Jakarta' */
        public string $timezone,

        /** Trace ID unik per pesan untuk observability */
        public string $traceId,

        /** ID pesan di platform (untuk reply-to, opsional) */
        public ?string $messageId = null,

        /** ID pesan yang di-reply (opsional) */
        public ?string $replyTo = null,

        /** Session identifier (untuk web chat history, opsional) */
        public ?string $sessionId = null,

        /** Metadata tambahan platform-specific (tidak dipakai business logic) */
        public array $metadata = [],
    ) {}

    /**
     * Source prefix untuk reference_number transaksi.
     * Delegasi ke ChatPlatform enum agar tetap konsisten.
     */
    public function sourcePrefix(): string
    {
        return $this->platform->sourcePrefix();
    }

    /**
     * Factory: buat ChatContext baru dengan traceId di-generate otomatis.
     *
     * @param array $metadata  Metadata tambahan dari platform
     */
    public static function make(
        ChatPlatform $platform,
        string       $conversationId,
        string       $locale    = 'id',
        string       $timezone  = 'Asia/Jakarta',
        ?string      $messageId = null,
        ?string      $replyTo   = null,
        ?string      $sessionId = null,
        array        $metadata  = [],
    ): self {
        return new self(
            platform:       $platform,
            conversationId: $conversationId,
            locale:         $locale,
            timezone:       $timezone,
            traceId:        (string) Str::ulid(),
            messageId:      $messageId,
            replyTo:        $replyTo,
            sessionId:      $sessionId,
            metadata:       $metadata,
        );
    }

    /**
     * Resolve locale dari tiga sumber dengan priority order.
     *
     * @param string|null $userLocale     Dari users.locale (DB)
     * @param string|null $platformLocale Dari metadata platform (mis. Telegram language_code)
     */
    public static function resolveLocale(
        ?string $userLocale,
        ?string $platformLocale,
    ): string {
        if (!blank($userLocale)) {
            return $userLocale;
        }

        if (!blank($platformLocale)) {
            return $platformLocale;
        }

        // Hardcode fallback ke 'id' — tidak bergantung config('app.locale')
        // agar tidak terpengaruh APP_LOCALE di .env yang bisa berbeda-beda.
        return 'id';
    }
}
