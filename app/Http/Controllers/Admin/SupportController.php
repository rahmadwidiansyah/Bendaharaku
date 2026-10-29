<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Support/Index', [
            'support_user' => $request->user(),
        ]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $query = $validated['query'];
        $users = User::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%")
              ->orWhere('telegram_id', 'like', "%{$query}%")
              ->orWhere('whatsapp_number', 'like', "%{$query}%");
        })->limit(20)->get(['id', 'name', 'email', 'created_at', 'email_verified_at']);

        // Log support access
        AdminAuditLog::log(
            $request->user(),
            'support_search',
            'User',
            null,
            null,
            ['query' => $query, 'results_count' => $users->count()],
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json([
            'results' => $users,
            'query' => $query,
        ]);
    }

    public function userDetail(Request $request, User $user)
    {
        // Log access to user detail
        AdminAuditLog::log(
            $request->user(),
            'support_view_user',
            'User',
            $user->id,
            null,
            null,
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        // Return limited detail (no financial data for support by default)
        return Inertia::render('Admin/Support/UserDetail', [
            'user' => $user->load([
                'wallets' => fn($q) => $q->select('id', 'user_id', 'name', 'currency', 'created_at'),
                'categories' => fn($q) => $q->select('id', 'user_id', 'name', 'color', 'created_at'),
            ]),
        ]);
    }

    public function logAccess(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        AdminAuditLog::log(
            $request->user(),
            'support_access_logged',
            'User',
            $user->id,
            null,
            ['reason' => $validated['reason']],
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Access logged',
        ]);
    }
}
