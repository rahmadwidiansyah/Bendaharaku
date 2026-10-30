<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Support\Admin\SortableTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('roles:id,name')->withCount('transactionLogs');

        // Pencarian: PostgreSQL LIKE bersifat case-sensitive, pakai ILIKE.
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('telegram_id', 'ilike', "%{$search}%")
                    ->orWhere('whatsapp_number', 'ilike', "%{$search}%");
            });
        }

        // Filter status verifikasi / akun.
        if ($request->filled('status')) {
            match ($request->input('status')) {
                'verified' => $query->whereNotNull('email_verified_at'),
                'unverified' => $query->whereNull('email_verified_at'),
                'active' => $query->where(fn ($q) => $q->whereNull('status')->orWhere('status', 'active')),
                'suspended' => $query->where('status', 'suspended'),
                default => null,
            };
        }

        // Filter aktivitas.
        if ($request->filled('activity')) {
            match ($request->input('activity')) {
                'online' => $query->where('last_active_at', '>=', now()->subMinutes(5)),
                '7d' => $query->where('last_active_at', '>=', now()->subDays(7)),
                '30d' => $query->where('last_active_at', '>=', now()->subDays(30)),
                'dormant' => $query->where(fn ($q) => $q->whereNull('last_active_at')->orWhere('last_active_at', '<', now()->subDays(30))),
                'never' => $query->whereNull('last_active_at'),
                default => null,
            };
        }

        if ($request->filled('role')) {
            $role = (string) $request->input('role');
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        $perPage = SortableTable::perPage($request, 15);

        $sort = SortableTable::apply($query, $request, [
            'name' => 'users.name',
            'email' => 'users.email',
            'status' => 'users.status',
            'verified' => 'users.email_verified_at',
            'joined' => 'users.created_at',
            'last_active' => 'users.last_active_at',
            'transactions' => 'transaction_logs_count',
            'role' => function ($query, string $direction) {
                $direction = $direction === 'asc' ? 'asc' : 'desc';
                $query->orderByRaw(
                    "(select roles.name from roles
                        inner join model_has_roles on roles.id = model_has_roles.role_id
                        where model_has_roles.model_id = users.id
                          and model_has_roles.model_type = ?
                        limit 1) {$direction}",
                    [User::class],
                );
            },
        ], 'joined', 'desc', 'users.id');

        $users = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => array_merge(
                $request->only(['search', 'status', 'activity', 'role']),
                SortableTable::filters($request, $sort, $perPage),
            ),
            'roles' => \Spatie\Permission\Models\Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function show(Request $request, User $user)
    {
        // Hanya data yang dibutuhkan halaman; jangan kirim seluruh transaksi user.
        $user->loadCount(['wallets', 'categories', 'transactionLogs']);
        $user->load('roles:id,name');

        AdminAuditLog::log(
            $request->user(),
            'user_viewed',
            'User',
            $user->id,
            null,
            null,
            ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
        );

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['active', 'suspended'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->canModify($request->user(), $user)) {
            return back()->with('error', 'Anda tidak memiliki izin mengubah status user ini.');
        }

        $oldStatus = $user->status ?? 'active';
        $user->update(['status' => $validated['status']]);

        AdminAuditLog::log(
            $request->user(),
            'user_status_changed',
            'User',
            $user->id,
            ['status' => $oldStatus],
            ['status' => $validated['status']],
            [
                'ip' => $request->ip(),
                'reason' => $validated['reason'] ?? null,
                'user_agent' => $request->userAgent(),
            ],
        );

        foreach ([7, 30, 90] as $range) {
            Cache::forget("admin:dashboard:{$range}d:v1");
        }

        return redirect()->back()->with('success', "Status {$user->name} diubah menjadi {$validated['status']}.");
    }

    public function bulkStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:users,id'],
            'status' => ['required', 'string', Rule::in(['active', 'suspended'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $users = User::whereIn('id', $validated['ids'])->get();
        $changed = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if (! $this->canModify($request->user(), $user)) {
                $skipped++;

                continue;
            }

            $oldStatus = $user->status ?? 'active';
            if ($oldStatus === $validated['status']) {
                continue;
            }

            $user->update(['status' => $validated['status']]);
            $changed++;

            AdminAuditLog::log(
                $request->user(),
                'user_status_changed',
                'User',
                $user->id,
                ['status' => $oldStatus],
                ['status' => $validated['status']],
                [
                    'ip' => $request->ip(),
                    'reason' => $validated['reason'] ?? null,
                    'bulk' => true,
                    'user_agent' => $request->userAgent(),
                ],
            );
        }

        $message = "{$changed} user diperbarui.";
        if ($skipped > 0) {
            $message .= " {$skipped} user dilewati karena izin/akun sendiri.";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Cegah eskalasi: support hanya boleh mengubah customer, admin tidak boleh
     * mengubah owner, dan siapa pun tidak boleh mengubah akunnya sendiri.
     */
    private function canModify(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        if ($actor->hasRole('owner')) {
            return true;
        }

        if ($target->hasRole('owner')) {
            return false;
        }

        if ($actor->hasRole('admin')) {
            return true;
        }

        // Support hanya untuk user non-staff.
        return ! $target->hasAnyRole(['owner', 'admin', 'support']);
    }
}
