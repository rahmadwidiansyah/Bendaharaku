<?php

declare(strict_types=1);

namespace App\Chat\Components;

/**
 * Kontrak untuk semua komponen ChatResponse.
 *
 * Setiap komponen merepresentasikan satu blok konten
 * yang platform-agnostic. Formatter bertanggung jawab
 * mengubah tiap komponen ke format platform masing-masing.
 *
 * Platform yang tidak mendukung komponen tertentu
 * (misal Discord tidak punya QuickReply) cukup
 * melewati komponen tersebut di Formatter-nya.
 */
interface ChatComponentInterface
{
    /**
     * Tipe komponen — digunakan Formatter untuk dispatch render.
     * Contoh: 'text', 'divider', 'transaction_card', 'error', dll.
     */
    public function type(): string;

    /**
     * Serialize ke array murni (untuk JSON response Web / logging).
     */
    public function toArray(): array;
}
