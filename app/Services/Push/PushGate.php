<?php

namespace App\Services\Push;

use App\Jobs\SendPushNotificationJob;
use App\Models\User;

/**
 * PushGate — gerbang pengiriman push dari trigger (job/command/action).
 *
 * Push dikirim segera (tanpa menunggu user 'away') jika user mengaktifkan
 * push dan punya minimal satu subscription.
 */
class PushGate
{
    public static function dispatch(User $user, array $payload): void
    {
        if (! $user->push_notifications) {
            return;
        }

        if (! $user->pushSubscriptions()->exists()) {
            return;
        }

        SendPushNotificationJob::dispatch($user->id, $payload);
    }
}