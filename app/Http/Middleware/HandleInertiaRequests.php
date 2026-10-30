<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'avatar_url' => $request->user()->avatar_url,
                    'theme' => $request->user()->theme ?? 'system',
                    'accent_color' => $request->user()->accent_color,
                    'category_icon_colored' => $request->user()->category_icon_colored,
                    'ui_theme' => $request->user()->ui_theme ?? 'default',
                    'ui_m3_color_scheme' => $request->user()->ui_m3_color_scheme ?? 'teal',
                    'bot_name' => $request->user()->bot_name ?? 'Ken-Chan',
                    'bot_avatar' => $request->user()->bot_avatar
                        ? asset('storage/' . $request->user()->bot_avatar)
                        : null,
                ] : null,
                'permissions' => $request->user()
                    ? $request->user()->getAllPermissions()->pluck('name')->values()->all()
                    : [],
                'roles' => $request->user()
                    ? $request->user()->getRoleNames()->values()->all()
                    : [],
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'vapidPublicKey' => config('services.webpush.vapid_public_key'),
        ];
    }
}
