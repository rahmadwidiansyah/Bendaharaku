<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tingkat keparahan error dalam Chat Engine.
 *
 * Formatter menggunakan severity untuk memutuskan
 * apakah perlu mengirim pesan terpisah, menampilkan
 * warning ringan, atau hanya log.
 */
enum ChatErrorSeverity: string
{
    /** Info saja — tidak mempengaruhi hasil */
    case Info     = 'info';

    /** Peringatan — hasil mungkin tidak sempurna */
    case Warning  = 'warning';

    /** Error — item gagal, tapi batch lain bisa lanjut */
    case Error    = 'error';

    /** Fatal — seluruh operasi dibatalkan */
    case Critical = 'critical';

    public function emoji(): string
    {
        return match ($this) {
            self::Info     => 'ℹ️',
            self::Warning  => '⚠️',
            self::Error    => '❌',
            self::Critical => '🚨',
        };
    }
}
