<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Intent dari sebuah pesan chat yang masuk ke Chat Engine.
 *
 * Digunakan oleh ChatResponse untuk memberi tahu Formatter
 * jenis konten apa yang perlu dirender, tanpa Formatter
 * perlu tahu detail business logic.
 */
enum ChatIntent: string
{
    /** Satu transaksi berhasil diproses */
    case SingleTransaction = 'single_transaction';

    /** Beberapa transaksi diproses sekaligus (partial success mungkin terjadi) */
    case MultiTransaction  = 'multi_transaction';

    /** Perintah sistem: /saldo, /help, /web, dll */
    case Command           = 'command';

    /** Pesan tidak dikenali atau AI tidak bisa memproses */
    case Unknown           = 'unknown';

    /** Terjadi error sebelum AI sempat memproses */
    case Error             = 'error';

    /** Transaksi disimpan sebagai draft karena confidence rendah */
    case Draft             = 'draft';
}
