<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\UserActivityLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log a unified activity entry. Auto-pruned after 7 days.
     */
    public static function log(
        ?int $userId,
        string $type,
        string $action,
        string $title,
        ?string $description = null,
        ?array $metadata = null,
    ): void {
        if ($userId === null) {
            $userId = Auth::id();
        }
        if ($userId === null) {
            return;
        }

        try {
            UserActivityLog::create([
                'user_id' => $userId,
                'type' => $type,
                'action' => $action,
                'title' => mb_strimwidth($title, 0, 255, '...'),
                'description' => $description ? mb_strimwidth($description, 0, 1000, '...') : null,
                'metadata' => $metadata,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent() ? mb_strimwidth(Request::userAgent(), 0, 500, '...') : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            logger()->warning('Failed to log activity: '.$e->getMessage(), ['type' => $type, 'action' => $action]);
        }
    }

    public static function forUser(Authenticatable $user, string $type, string $action, string $title, ?string $description = null, ?array $metadata = null): void
    {
        self::log((int) $user->getAuthIdentifier(), $type, $action, $title, $description, $metadata);
    }
}
