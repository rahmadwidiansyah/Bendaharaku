<?php

namespace App\Services\Push;

use App\Jobs\SendPushNotificationJob;
use App\Models\User;

/**
 * PushGate — gerbang pengiriman push dari trigger (job/command/action).
 *
 * Push HANYA dikirim jika user 'away' (tidak membuka tab Bendaharaku),
 * push diaktifkan, dan punya minimal satu subscription.
 */
class PushGate
{
    public static function dispatchIfAway(User $user, array $payload): void
    {
        if (! $user->push_notifications) {
            return;
        }

        if (! app(PresenceService::class)->isAway($user->id)) {
            return;
        }

        if (! $user->pushSubscriptions()->exists()) {
            return;
        }

        SendPushNotificationJob::dispatch($user->id, $payload);
    }
}
