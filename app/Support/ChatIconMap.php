<?php

declare(strict_types=1);

namespace App\Support;

/**
 * ChatIconMap — map emoji legacy → lucide kebab untuk Web chat.
 * Telegram tetap pakai emoji (TelegramFormatter), Web pakai lucide via WebFormatter.
 */
class ChatIconMap
{
    public const EMOJI_TO_LUCIDE = [
        '↑' => 'trending-up',
        '↓' => 'trending-down',
        '⇄' => 'arrow-left-right',
        '🤝' => 'handshake',
        '•' => 'circle-dot',
        '🟢' => 'trending-up',
        '🔴' => 'trending-down',
        '🔵' => 'arrow-left-right',
        '📂' => 'folder',
        '👛' => 'wallet',
        '📥' => 'wallet',
        '👤' => 'user',
        '📅' => 'calendar',
        '×' => 'x',
        '●' => 'check-circle-2',
        '◐' => 'clock-3',
        '💬' => 'message-circle',
        '📡' => 'send',
        '📱' => 'smartphone',
        '🎮' => 'gamepad-2',
        '⚡' => 'zap',
        '✏️' => 'pencil',
        '🌐' => 'globe',
        '🎯' => 'target',
        '🤖' => 'bot',
        '⏱' => 'clock-3',
        '📊' => 'bar-chart-3',
        '✅' => 'check',
        '💳' => 'credit-card',
        '💰' => 'wallet',
        '💸' => 'wallet',
        '💵' => 'banknote',
        '🏷️' => 'tag',
        '📈' => 'trending-up',
        '📋' => 'clipboard-list',
        '📄' => 'file-text',
        '📉' => 'line-chart',
        '❓' => 'circle-help',
        '👋' => 'hand',
        '⚙️' => 'settings',
        '🏦' => 'building-2',
        '🏪' => 'store',
        '🍔' => 'utensils',
        '🚗' => 'car',
        '📚' => 'book-open',
        '🏠' => 'house',
        '🛒' => 'shopping-cart',
        '☕' => 'coffee',
        '🧴' => 'droplets',
        '👕' => 'shirt',
        '🛠️' => 'wrench',
        '🎁' => 'gift',
        '🚀' => 'rocket',
        '🍃' => 'leaf',
        '🔄' => 'refresh-cw',
        '📤' => 'upload',
        '🤑' => 'hand-coins',
        '✦' => 'sparkles',
        '·' => 'dot',
    ];

    public static function toLucide(?string $icon): string
    {
        if ($icon === null || trim($icon) === '') {
            return 'circle-help';
        }
        $raw = trim($icon);

        // URL / file path → biarkan (frontend akan render <img>)
        if (str_starts_with($raw, 'http') || str_contains($raw, '.') || str_contains($raw, '/')) {
            return $raw;
        }

        // already lucide kebab
        if (preg_match('/^[a-z0-9-]+$/', $raw) && strlen($raw) > 1) {
            return $raw;
        }
        if (isset(self::EMOJI_TO_LUCIDE[$raw])) {
            return self::EMOJI_TO_LUCIDE[$raw];
        }
        $stripped = str_replace(["\u{FE0F}", "\u{200D}"], '', $raw);
        if (isset(self::EMOJI_TO_LUCIDE[$stripped])) {
            return self::EMOJI_TO_LUCIDE[$stripped];
        }

        return 'circle-help';
    }
}
