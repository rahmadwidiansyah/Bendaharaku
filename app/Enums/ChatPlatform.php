<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Platform yang didukung oleh Chat Engine.
 *
 * Setiap platform punya Adapter dan Formatter sendiri.
 * Business logic tidak boleh bergantung pada nilai enum ini
 * kecuali untuk keperluan logging/observability.
 */
enum ChatPlatform: string
{
    case Telegram = 'telegram';
    case WhatsApp = 'whatsapp';
    case Discord = 'discord';
    case Web = 'web';
    case Slack = 'slack';
    case Line = 'line';
    case Messenger = 'messenger';
    case Unknown = 'unknown';

    /**
     * Source prefix untuk reference_number transaksi.
     * Misal: TEL-01JX..., WEB-01JX...
     */
    public function sourcePrefix(): string
    {
        return match ($this) {
            self::Telegram => 'TEL',
            self::WhatsApp => 'WA',
            self::Discord => 'DSC',
            self::Web => 'WEB',
            self::Slack => 'SLK',
            self::Line => 'LIN',
            self::Messenger => 'MSG',
            self::Unknown => 'UNK',
        };
    }

    /**
     * Default locale untuk platform jika user tidak set preferensi.
     * Bisa di-override dari metadata platform.
     */
    public function defaultLocale(): string
    {
        return 'id'; // Bahasa Indonesia sebagai fallback universal
    }
}
