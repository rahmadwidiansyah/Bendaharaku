<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\Push\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PushNotificationController — endpoint subscription & presensi Web Push.
 *
 * - subscribe: simpan PushSubscription browser user (upsert per endpoint)
 * - unsubscribe: hapus subscription
 * - presence: sinyal aktif/away dari browser (untuk gate pengiriman push)
 */
class PushNotificationController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:2048'],
            'p256dh' => ['required', 'string', 'max:512'],
            'auth' => ['required', 'string', 'max:512'],
        ]);

        $user = $request->user();

        PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'user_id' => $user->id,
                'p256dh' => $validated['p256dh'],
                'auth' => $validated['auth'],
                'user_agent' => mb_substr($request->userAgent() ?? '', 0, 255),
            ]
        );

        return response()->json(['success' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:2048'],
        ]);

        PushSubscription::where('endpoint', $validated['endpoint'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['success' => true]);
    }

    public function presence(Request $request, PresenceService $presence): JsonResponse
    {
        $validated = $request->validate([
            'state' => ['required', 'string', 'in:active,away'],
        ]);

        $userId = $request->user()->id;

        if ($validated['state'] === 'active') {
            $presence->markActive($userId);
        } else {
            $presence->markAway($userId);
        }

        return response()->json(['success' => true]);
    }
}
