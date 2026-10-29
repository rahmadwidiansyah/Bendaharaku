<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by verification status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($status === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        // Pagination
        $per_page = $request->input('per_page', 15);
        $users = $query->latest('created_at')->paginate($per_page);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'per_page']),
        ]);
    }

    public function show(User $user)
    {
        return Inertia::render('Admin/Users/Show', [
            'user' => $user->load(['wallets', 'categories', 'transactions']),
        ]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,suspended'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $oldStatus = $user->status ?? 'active';
        $newStatus = $validated['status'];

        $user->update(['status' => $newStatus]);

        // Log to audit trail
        AdminAuditLog::log(
            $request->user(),
            'user_status_changed',
            'User',
            $user->id,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            [
                'ip' => $request->ip(),
                'reason' => $validated['reason'] ?? null,
                'user_agent' => $request->userAgent(),
            ]
        );

        return redirect()->back()->with('success', "User status updated to {$newStatus}");
    }
}
