<?php

declare(strict_types=1);

namespace App\Chat\Contracts;

use App\Chat\DTOs\ChatContext;
use App\Chat\DTOs\ChatResponse;

/**
 * Kontrak untuk semua platform formatter.
 *
 * Implementasi: TelegramFormatter, WebFormatter, WhatsAppFormatter, DiscordFormatter, dll.
 *
 * Formatter HANYA boleh:
 * - Membaca ChatResponse dan ChatContext
 * - Memanggil trans() / __() untuk teks (sesuai $context->locale)
 * - Mengubah components[] menjadi representasi platform
 *
 * Formatter TIDAK boleh:
 * - Memanggil database
 * - Memanggil AI
 * - Mengandung business logic
 * - Hardcode string teks (gunakan translation key)
 *
 * Return type string|array:
 * - string untuk platform text-based (Telegram, WhatsApp, Discord)
 * - array untuk Web (JSON response dengan structure kaya)
 */
interface ChatFormatterInterface
{
    /**
     * Format ChatResponse menjadi output siap kirim untuk platform ini.
     *
     * @return string|array String untuk text platform, array untuk web/API
     */
    public function format(ChatResponse $response, ChatContext $context): string|array;

    /**
     * Apakah formatter ini mendukung platform yang diberikan.
     * Digunakan oleh FormatterRegistry untuk resolve formatter yang tepat.
     */
    public function supports(string $platform): bool;
}
