<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak user yang berstatus `suspended` dari seluruh request web.
 *
 * Status suspend harus berefek pada session yang sudah berjalan, bukan hanya
 * pada percobaan login berikutnya.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status === 'suspended') {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akun Anda sedang ditangguhkan. Hubungi dukungan.',
                ], 403);
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Akun Anda sedang ditangguhkan. Hubungi dukungan.']);
        }

        return $next($request);
    }
}
