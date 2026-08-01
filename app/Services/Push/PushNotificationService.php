<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * PushNotificationService — mengirim payload Web Push ke semua subscription user.
 *
 * Tanggung jawab:
 * - Hormati flag user->push_notifications
 * - Hapus subscription basi (410 Gone / 404 Not Found)
 * - Error pengiriman lain hanya di-log, tidak menggagalkan job utama
 */
class PushNotificationService
{
    public function sendToUser(int $userId, array $payload): void
    {
        $user = User::find($userId);
        if (! $user || ! $user->push_notifications) {
            return;
        }

        $subscriptions = $user->pushSubscriptions()->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $publicKey = config('services.webpush.vapid_public_key');
        $privateKey = config('services.webpush.vapid_private_key');
        if (! $publicKey || ! $privateKey) {
            Log::warning('PushNotificationService: VAPID keys belum dikonfigurasi', ['user_id' => $userId]);

            return;
        }

        $webPush = $this->makeWebPush($publicKey, $privateKey);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                new Subscription($subscription->endpoint, $subscription->p256dh, $subscription->auth),
                json_encode($payload, JSON_UNESCAPED_UNICODE),
                ['TTL' => (int) config('bendaharaku.push.notification_ttl_seconds', 86400)],
                [
                    'VAPID' => [
                        'subject' => config('services.webpush.vapid_subject'),
                        'publicKey' => $publicKey,
                        'privateKey' => $privateKey,
                    ],
                ],
            );
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                $this->deleteSubscriptionByEndpoint($report->getEndpoint());
                Log::info('PushNotificationService: subscription basi dihapus', ['user_id' => $userId]);
            } elseif (! $report->isSuccess()) {
                Log::warning('PushNotificationService: gagal kirim push', [
                    'user_id' => $userId,
                    'endpoint' => $report->getEndpoint(),
                    'reason' => $report->getReason(),
                ]);
            }
        }
    }

    protected function makeWebPush(string $publicKey, string $privateKey): WebPush
    {
        return new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.vapid_subject'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);
    }

    private function deleteSubscriptionByEndpoint(string $endpoint): void
    {
        PushSubscription::where('endpoint', $endpoint)->delete();
    }
}
