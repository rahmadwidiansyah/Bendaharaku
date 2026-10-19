<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\User;
use App\Support\ActivityLogger;

/**
 * Satu entri ringkas per pesan saat LLM pool di-bypass fallback
 * (pool success setelah pindah kandidat, atau pool gagal → fallback lokal).
 * Detail per-attempt tetap tersimpan di ai_parse_logs.routing_attempts.
 */
class AiFallbackActivityLogger
{
    public static function log(User $user, string $from, string $to, ?array $routing = null): void
    {
        $from = trim($from);
        $to = trim($to);

        if ($from === '' || $to === '') {
            return;
        }

        $routing ??= [];
        $reason = $routing['fallback_reason'] ?? null;

        ActivityLogger::forUser(
            $user,
            'ai',
            'fallback',
            "AI fallback: {$from} → {$to}",
            $reason ? 'Alasan: '.$reason : null,
            array_filter([
                'trace_id' => $routing['trace_id'] ?? null,
                'selection_method' => $routing['selection_method'] ?? null,
                'fallback_from' => $from,
                'fallback_to' => $to,
                'fallback_reason' => $reason,
                'final_status' => $routing['final_status'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
        );
    }
}
