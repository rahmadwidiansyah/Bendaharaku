<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Cache;

/**
 * PresenceService — melacak kehadiran user di aplikasi (browser terbuka vs tidak).
 *
 * Browser mengirim sinyal 'active' saat terlihat (visibilitychange visible)
 * dan 'away' saat hidden/tab ditutup, plus heartbeat berkala saat visible.
 * Push hanya boleh dikirim jika user 'away' (tidak membuka tab Bendaharaku).
 */
class PresenceService
{
    private function key(int $userId): string
    {
        return 'presence:'.$userId;
    }

    public function markActive(int $userId): void
    {
        Cache::put($this->key($userId), [
            'state' => 'active',
            'at' => now()->timestamp,
        ], now()->addMinutes(5));
    }

    public function markAway(int $userId): void
    {
        Cache::put($this->key($userId), [
            'state' => 'away',
            'at' => now()->timestamp,
        ], now()->addMinutes(5));
    }

    public function state(int $userId): ?array
    {
        return Cache::get($this->key($userId));
    }

    /**
     * User dianggap away jika:
     * - tidak pernah mengirim sinyal (tidak ada data), ATAU
     * - sinyal terakhir 'away', ATAU
     * - sinyal 'active' sudah kedaluwarsa (> threshold detik).
     */
    public function isAway(int $userId): bool
    {
        $data = $this->state($userId);
        if ($data === null) {
            return true;
        }

        if ($data['state'] === 'away') {
            return true;
        }

        return (now()->timestamp - (int) $data['at']) > config('bendaharaku.push.presence_ttl_seconds', 120);
    }
}
