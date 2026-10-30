<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private const STAFF_ROLES = ['owner', 'admin', 'support'];

    public function __invoke(Request $request)
    {
        $range = (int) $request->input('range', 30);
        if (! in_array($range, [7, 30, 90], true)) {
            $range = 30;
        }

        $data = Cache::remember("admin:dashboard:{$range}d:v1", 60, fn () => $this->build($range));

        return Inertia::render('Admin/Dashboard', [
            'stats' => $data['stats'],
            'charts' => $data['charts'],
            'attention' => $data['attention'],
            'recent_users' => $data['recent_users'],
            'recent_audit' => $data['recent_audit'],
            'roles' => $data['roles'],
            'system' => $data['system'],
            'range' => $range,
            'generated_at' => $data['generated_at'],
            'admin_user' => $request->user(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function build(int $range): array
    {
        $now = now();
        $start = $now->copy()->subDays($range - 1)->startOfDay();
        $customerQuery = User::query()->customers();

        $stats = [
            'mau' => (clone $customerQuery)->where('last_active_at', '>=', $now->copy()->subDays(30))->count(),
            'wau' => (clone $customerQuery)->where('last_active_at', '>=', $now->copy()->subDays(7))->count(),
            'dau' => (clone $customerQuery)->where('last_active_at', '>=', $now->copy()->subDay())->count(),
            'online_now' => (clone $customerQuery)->where('last_active_at', '>=', $now->copy()->subMinutes(5))->count(),
            'total_users' => (clone $customerQuery)->count(),
            'new_users' => (clone $customerQuery)->where('created_at', '>=', $start)->count(),
            'verified_users' => (clone $customerQuery)->whereNotNull('email_verified_at')->count(),
            'suspended_users' => User::query()
                ->where('status', 'suspended')
                ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', self::STAFF_ROLES))
                ->count(),
            'transactions_range' => DB::table('transaction_logs')->where('created_at', '>=', $start)->count(),
            'transactions_total' => DB::table('transaction_logs')->count(),
            'failed_ai_24h' => DB::table('activity_logs')
                ->where('status', 'error')
                ->where('created_at', '>=', $now->copy()->subDay())
                ->count(),
        ];

        $activeByDay = $this->activeUsersByDay($start, $now);
        $newByDay = (clone $customerQuery)
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');
        $transactionsByDay = DB::table('transaction_logs')
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $activeSeries = [];
        $newSeries = [];
        $transactionSeries = [];

        for ($i = 0; $i < $range; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $labels[] = $day;
            $activeSeries[] = (int) ($activeByDay[$day] ?? 0);
            $newSeries[] = (int) ($newByDay[$day] ?? 0);
            $transactionSeries[] = (int) ($transactionsByDay[$day] ?? 0);
        }

        $roles = DB::table('model_has_roles as mhr')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->selectRaw('r.name as role, count(*) as total')
            ->groupBy('r.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['role' => $row->role, 'total' => (int) $row->total])
            ->all();

        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $pendingJobs = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $lastFailedJobAt = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->max('failed_at')
            : null;

        return [
            'stats' => $stats,
            'charts' => [
                'labels' => $labels,
                'active' => $activeSeries,
                'new_users' => $newSeries,
                'transactions' => $transactionSeries,
            ],
            'attention' => [
                [
                    'key' => 'failed_jobs',
                    'label' => 'Failed queue jobs',
                    'value' => $failedJobs,
                    'severity' => $failedJobs > 0 ? 'critical' : 'ok',
                    'href' => null,
                ],
                [
                    'key' => 'failed_ai',
                    'label' => 'AI errors (24 jam)',
                    'value' => $stats['failed_ai_24h'],
                    'severity' => $stats['failed_ai_24h'] > 0 ? 'warning' : 'ok',
                    'href' => route('admin.ai_logs.index').'?status=error',
                ],
                [
                    'key' => 'suspended',
                    'label' => 'User ditangguhkan',
                    'value' => $stats['suspended_users'],
                    'severity' => $stats['suspended_users'] > 0 ? 'warning' : 'ok',
                    'href' => route('admin.users.index').'?status=suspended',
                ],
                [
                    'key' => 'unverified',
                    'label' => 'Belum verifikasi',
                    'value' => max(0, $stats['total_users'] - $stats['verified_users']),
                    'severity' => 'info',
                    'href' => route('admin.users.index').'?status=unverified',
                ],
            ],
            'recent_users' => User::query()
                ->customers()
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'email', 'status', 'created_at', 'last_active_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status ?? 'active',
                    'created_at' => $user->created_at?->toIso8601String(),
                    'last_active_at' => $user->last_active_at?->toIso8601String(),
                ])
                ->all(),
            'recent_audit' => AdminAuditLog::query()
                ->with('admin:id,name')
                ->latest()
                ->limit(5)
                ->get(['id', 'admin_id', 'action', 'target_type', 'target_id', 'success', 'created_at'])
                ->map(fn (AdminAuditLog $log) => [
                    'id' => $log->id,
                    'admin' => $log->admin?->name,
                    'action' => $log->action,
                    'target' => trim(($log->target_type ?? '').' #'.($log->target_id ?? '')),
                    'success' => (bool) $log->success,
                    'created_at' => $log->created_at?->toIso8601String(),
                ])
                ->all(),
            'roles' => $roles,
            'system' => [
                'queue_failed' => $failedJobs,
                'queue_pending' => $pendingJobs,
                'last_failed_job_at' => $lastFailedJobAt ? Carbon::parse($lastFailedJobAt)->toIso8601String() : null,
                'last_activity_at' => DB::table('user_activity_daily')->max('last_seen_at'),
            ],
            'generated_at' => $now->toIso8601String(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function activeUsersByDay(Carbon $start, Carbon $end): array
    {
        return DB::table('user_activity_daily as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->whereBetween('a.activity_date', [$start->toDateString(), $end->toDateString()])
            ->where(fn ($q) => $q->whereNull('u.status')->orWhere('u.status', '!=', 'suspended'))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('model_has_roles as mhr')
                    ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                    ->whereColumn('mhr.model_id', 'u.id')
                    ->where('mhr.model_type', User::class)
                    ->whereIn('r.name', self::STAFF_ROLES);
            })
            ->selectRaw('a.activity_date as day, count(distinct a.user_id) as total')
            ->groupBy('a.activity_date')
            ->pluck('total', 'day')
            ->map(fn ($value) => (int) $value)
            ->all();
    }
}
