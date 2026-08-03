<?php

declare(strict_types=1);

/**
 * Translation keys untuk Web Push Notifications — Bahasa Indonesia.
 */
return [

    'months' => [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ],

    'chat' => [
        'reply_failed' => 'Balasan chat kamu gagal diproses. Coba lagi nanti.',
    ],

    'budget' => [
        'created_title' => 'Budget siap',
        'created_body' => 'Budget :month kamu sudah dibuat. Yuk dicek!',
        'failed_title' => 'Generate budget gagal',
        'failed_body' => 'Budget :month gagal dibuat. Coba lagi dari halaman Budgeting.',
        'over_title' => 'Budget terlewati',
        'over_body' => 'Budget ":group" sudah terlewati bulan ini.',
    ],

    'loan' => [
        'day_before_title' => 'Jatuh tempo besok',
        'day_before_body' => ':subject (:type) jatuh tempo besok — sisa :amount.',
        'due_title' => 'Jatuh tempo hari ini',
        'due_body' => ':subject (:type) jatuh tempo hari ini — sisa :amount.',
        'upcoming_title' => 'Segera jatuh tempo',
        'upcoming_body' => ':subject (:type) jatuh tempo :days hari lagi — sisa :amount.',
        'overdue_title' => 'Terlewat jatuh tempo',
        'overdue_body' => ':subject (:type) sudah terlambat :days hari — sisa :amount.',
        'type_debt' => 'hutang',
        'type_receivable' => 'piutang',
    ],
];
