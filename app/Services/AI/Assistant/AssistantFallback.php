<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant;

/**
 * AssistantFallback — template respons berbasis STATUS AKTUAL dari backend.
 *
 * Dipakai ketika model gagal menghasilkan AssistantResponse yang valid
 * (JSON rusak, message kosong, status tidak dikenal). Hasil transaksi yang
 * sudah tersimpan TETAP terwakili — kegagalan pembuatan pesan tidak pernah
 * membatalkan transaksi (spesifikasi §8).
 *
 * Template bahasa di lang/{id,en}/assistant.php (assistant.fallback.*).
 */
final class AssistantFallback
{
    public static function messageFor(string $status): string
    {
        $status = in_array($status, AssistantResponse::STATUSES, true) ? $status : 'conversation';

        return (string) trans('assistant.fallback.'.$status);
    }

    public static function response(string $status): AssistantResponse
    {
        return AssistantResponse::fallback($status);
    }
}
