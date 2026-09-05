<?php

declare(strict_types=1);

namespace App\Chat;

use App\Support\ChatIconMap;

/**
 * ChatCommandRegistry — Single Source of Truth untuk semua command chat.
 *
 * Semua platform (Telegram, Web, WhatsApp, Discord, dsb) HARUS menggunakan
 * registry ini. Tidak ada command yang di-hardcode di platform masing-masing.
 *
 * Setiap command memiliki:
 *   command     — String command dengan prefix slash (wajib)
 *   category    — Kelompok command untuk UI grouping
 *   icon        — Emoji atau icon identifier
 *   description — Translation key untuk deskripsi singkat
 *   hint        — Translation key untuk hint tambahan (opsional)
 *   platforms   — Platform yang mendukung command ini (kosong = semua)
 *   hidden      — Tidak ditampilkan di UI tapi tetap berfungsi
 *
 * Untuk menambah command baru: cukup tambahkan entry di DEFINITIONS.
 * Semua platform yang subscribe ke registry ini otomatis mendapat command baru.
 */
class ChatCommandRegistry
{
    // ── Command Categories ────────────────────────────────────────

    public const CAT_GENERAL = 'general';

    public const CAT_FINANCE = 'finance';

    public const CAT_REPORT = 'report';

    public const CAT_SETTINGS = 'settings';

    // ── Command Definitions ───────────────────────────────────────

    private const DEFINITIONS = [
        // ── General ──────────────────────────────────────────────
        [
            'command' => '/help',
            'category' => self::CAT_GENERAL,
            'icon' => '❓',
            'description' => 'chat.commands.help.description',
            'hint' => 'chat.commands.help.hint',
            'platforms' => [],
            'hidden' => false,
        ],
        [
            'command' => '/start',
            'category' => self::CAT_GENERAL,
            'icon' => '👋',
            'description' => 'chat.commands.start.description',
            'hint' => null,
            'platforms' => ['telegram'],
            'hidden' => true,  // Telegram-only, disembunyikan di Web UI
        ],

        // ── Finance ──────────────────────────────────────────────
        [
            'command' => '/saldo',
            'category' => self::CAT_FINANCE,
            'icon' => '💰',
            'description' => 'chat.commands.saldo.description',
            'hint' => 'chat.commands.saldo.hint',
            'platforms' => [],
            'hidden' => false,
        ],
        [
            'command' => '/wallet',
            'category' => self::CAT_FINANCE,
            'icon' => '👛',
            'description' => 'chat.commands.wallet.description',
            'hint' => null,
            'platforms' => [],
            'hidden' => false,
        ],
        [
            'command' => '/kategori',
            'category' => self::CAT_FINANCE,
            'icon' => '🏷️',
            'description' => 'chat.commands.kategori.description',
            'hint' => null,
            'platforms' => [],
            'hidden' => false,
        ],
        [
            'command' => '/aset',
            'category' => self::CAT_FINANCE,
            'icon' => '📈',
            'description' => 'chat.commands.aset.description',
            'hint' => null,
            'platforms' => [],
            'hidden' => false,
        ],

        // ── Transactions ─────────────────────────────────────────
        [
            'command' => '/transaksi',
            'category' => self::CAT_FINANCE,
            'icon' => '📋',
            'description' => 'chat.commands.transaksi.description',
            'hint' => 'chat.commands.transaksi.hint',
            'platforms' => [],
            'hidden' => false,
        ],
        [
            'command' => '/pemasukan',
            'category' => self::CAT_FINANCE,
            'icon' => '🟢',
            'description' => 'chat.commands.pemasukan.description',
            'hint' => null,
            'platforms' => [],
            'hidden' => false,
        ],
        [
            'command' => '/pengeluaran',
            'category' => self::CAT_FINANCE,
            'icon' => '🔴',
            'description' => 'chat.commands.pengeluaran.description',
            'hint' => null,
            'platforms' => [],
            'hidden' => false,
        ],
        [
            'command' => '/transfer',
            'category' => self::CAT_FINANCE,
            'icon' => '🔵',
            'description' => 'chat.commands.transfer.description',
            'hint' => null,
            'platforms' => [],
            'hidden' => false,
        ],

        // ── Reports ──────────────────────────────────────────────
        [
            'command' => '/ringkasan',
            'category' => self::CAT_REPORT,
            'icon' => '📊',
            'description' => 'chat.commands.ringkasan.description',
            'hint' => 'chat.commands.ringkasan.hint',
            'platforms' => [],
            'hidden' => false,
        ],
        [
            'command' => '/laporan',
            'category' => self::CAT_REPORT,
            'icon' => '📄',
            'description' => 'chat.commands.laporan.description',
            'hint' => null,
            'platforms' => [],
            'hidden' => false,
        ],
        [
            'command' => '/statistik',
            'category' => self::CAT_REPORT,
            'icon' => '📉',
            'description' => 'chat.commands.statistik.description',
            'hint' => null,
            'platforms' => [],
            'hidden' => false,
        ],

        // ── Settings ─────────────────────────────────────────────
        [
            'command' => '/settings',
            'category' => self::CAT_SETTINGS,
            'icon' => '⚙️',
            'description' => 'chat.commands.settings.description',
            'hint' => null,
            'platforms' => ['web'],
            'hidden' => false,
        ],
        [
            'command' => '/web',
            'category' => self::CAT_SETTINGS,
            'icon' => '🌐',
            'description' => 'chat.commands.web.description',
            'hint' => null,
            'platforms' => ['telegram'],
            'hidden' => true,  // Telegram-only
        ],
    ];

    // ── Public API ────────────────────────────────────────────────

    /**
     * Semua command yang aktif untuk platform tertentu.
     * Platform kosong = semua command yang tidak di-filter per platform.
     *
     * @param  string  $platform  'web' | 'telegram' | '' (all)
     * @param  bool  $includeHidden  Sertakan command yang hidden
     * @return array<int, array>
     */
    public function forPlatform(string $platform = '', bool $includeHidden = false): array
    {
        return array_values(array_filter(
            self::DEFINITIONS,
            function (array $cmd) use ($platform, $includeHidden) {
                // Filter hidden
                if (! $includeHidden && $cmd['hidden']) {
                    return false;
                }

                // Filter platform
                if (! empty($cmd['platforms']) && ! empty($platform)) {
                    return in_array($platform, $cmd['platforms'], true);
                }

                // Command platform-specific tidak masuk ke platform lain
                if (! empty($cmd['platforms']) && empty($platform)) {
                    return false;
                }

                return true;
            }
        ));
    }

    /**
     * Semua command untuk Web UI (non-hidden, non-telegram-only).
     *
     * @return array<int, array>
     */
    public function forWeb(): array
    {
        return $this->forPlatform('web', includeHidden: false);
    }

    /**
     * Semua command untuk Telegram (non-hidden, termasuk telegram-specific).
     *
     * @return array<int, array>
     */
    public function forTelegram(): array
    {
        $all = self::DEFINITIONS;

        return array_values(array_filter($all, function (array $cmd) {
            // Telegram mendapat semua command non-hidden, plus yang khusus telegram
            if (empty($cmd['platforms'])) {
                return ! $cmd['hidden'];
            }

            return in_array('telegram', $cmd['platforms'], true);
        }));
    }

    /**
     * Periksa apakah string adalah command yang valid.
     */
    public function isCommand(string $text): bool
    {
        $text = strtolower(trim($text));
        foreach (self::DEFINITIONS as $cmd) {
            if (str_starts_with($text, $cmd['command'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cari definisi command berdasarkan string.
     * Cocokkan prefix sehingga '/saldo btc' cocok dengan '/saldo'.
     */
    public function find(string $text): ?array
    {
        $text = strtolower(trim($text));
        foreach (self::DEFINITIONS as $cmd) {
            if ($text === $cmd['command'] || str_starts_with($text, $cmd['command'].' ')) {
                return $cmd;
            }
        }

        return null;
    }

    /**
     * Serialize untuk JSON API response ke frontend.
     * Translation keys di-resolve di sini menggunakan locale aktif.
     *
     * @param  string  $platform  Target platform
     * @param  string  $locale  Locale untuk terjemahan
     * @return array<int, array>
     */
    public function toApiResponse(string $platform = 'web', string $locale = 'id'): array
    {
        return array_map(function (array $cmd) use ($locale, $platform) {
            $icon = $cmd['icon'];
            // Web chat harus pakai lucide, Telegram tetap emoji
            if ($platform === 'web') {
                $icon = ChatIconMap::toLucide($icon);
            }

            return [
                'command' => $cmd['command'],
                'category' => $cmd['category'],
                'icon' => $icon,
                'description' => trans($cmd['description'], [], $locale),
                'hint' => $cmd['hint'] ? trans($cmd['hint'], [], $locale) : null,
            ];
        }, $this->forPlatform($platform, includeHidden: false));
    }
}
