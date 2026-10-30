<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencatat aktivitas user untuk metrik dashboard admin.
 *
 * - `users.last_active_at`  : sinyal terakhir aktif (dipakai KPI online/MAU).
 * - `user_activity_daily`   : satu baris per user per hari (tren DAU/WAU).
 *
 * Write di-throttle maksimal sekali per 5 menit per user agar tidak membebani
 * database pada setiap request.
 */
class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (! $user || $user->status === 'suspended' || ! $request->isMethod('GET')) {
            return $response;
        }

        // Request Inertia/partial juga dihitung; halaman statis tidak.
        $bucket = intdiv(time(), 300);
        if (! Cache::add("user:{$user->id}:activity:{$bucket}", true, 300)) {
            return $response;
        }

        $now = now();

        try {
            DB::table('user_activity_daily')->upsert(
                [[
                    'user_id' => $user->id,
                    'activity_date' => $now->toDateString(),
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                ]],
                ['user_id', 'activity_date'],
                ['last_seen_at' => $now],
            );

            $user->forceFill(['last_active_at' => $now])->saveQuietly();
        } catch (\Throwable $e) {
            // Jangan sampai kegagalan metrik memutus request user.
            logger()->warning('Failed to track user activity: '.$e->getMessage());
        }

        return $response;
    }
}
