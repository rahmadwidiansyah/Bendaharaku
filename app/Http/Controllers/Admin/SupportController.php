<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Support\Admin\SortableTable;
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
            'sort' => ['nullable', 'string', 'max:40'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $term = trim($validated['query']);

        $query = User::query();

        $query->where(function ($q) use ($term) {
            $q->where('name', 'ilike', "%{$term}%")
                ->orWhere('email', 'ilike', "%{$term}%")
                ->orWhere('telegram_id', 'ilike', "%{$term}%")
                ->orWhere('whatsapp_number', 'ilike', "%{$term}%");
        });

        $sort = SortableTable::apply(
            $query,
            $request,
            [
                'name' => 'users.name',
                'email' => 'users.email',
                'joined' => 'users.created_at',
                'verified' => 'users.email_verified_at',
                'last_active' => 'users.last_active_at',
            ],
            'joined',
            'desc',
            'users.id',
        );

        $users = $query->limit(20)
            ->get(['id', 'name', 'email', 'created_at', 'email_verified_at', 'last_active_at', 'status'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'last_active_at' => $user->last_active_at?->toIso8601String(),
                'status' => $user->status ?? 'active',
            ]);

        AdminAuditLog::log(
            $request->user(),
            'support_search',
            'User',
            null,
            null,
            ['query' => $term, 'results_count' => $users->count()],
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        );

        return response()->json([
            'results' => $users,
            'query' => $term,
            'sort' => $sort,
        ]);
    }

    public function userDetail(Request $request, User $user)
    {
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
            ],
        );

        // Data minimal untuk support: tanpa saldo/transaksi finansial.
        return Inertia::render('Admin/Support/UserDetail', [
            'user' => $user->load([
                'wallets' => fn ($q) => $q->select('id', 'user_id', 'name', 'is_active', 'created_at'),
                'categories' => fn ($q) => $q->select('id', 'user_id', 'category_name', 'created_at'),
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
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Access logged',
        ]);
    }
}
