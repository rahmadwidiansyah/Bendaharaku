<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Support\Admin\SortableTable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'admin_id' => ['nullable', 'integer'],
            'success' => ['nullable', 'boolean'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        $query = AdminAuditLog::query()
            ->leftJoin('users', 'users.id', '=', 'admin_audit_logs.admin_id')
            ->select('admin_audit_logs.*', 'users.name as admin_name')
            ->with('admin');

        if ($request->filled('action')) {
            $query->where('admin_audit_logs.action', $request->input('action'));
        }

        if ($request->filled('admin_id')) {
            $query->where('admin_audit_logs.admin_id', $request->input('admin_id'));
        }

        if ($request->filled('success')) {
            $query->where('admin_audit_logs.success', $request->boolean('success'));
        }

        if ($request->filled('from_date')) {
            $request->validate(['from_date' => ['date']]);
            $query->where('admin_audit_logs.created_at', '>=', $request->date('from_date')->startOfDay());
        }

        if ($request->filled('to_date')) {
            $request->validate(['to_date' => ['date']]);
            $query->where('admin_audit_logs.created_at', '<=', $request->date('to_date')->endOfDay());
        }

        $perPage = SortableTable::perPage($request, 20);

        $sort = SortableTable::apply($query, $request, [
            'created_at' => 'admin_audit_logs.created_at',
            'action' => 'admin_audit_logs.action',
            'admin' => 'users.name',
            'target_type' => 'admin_audit_logs.target_type',
            'success' => 'admin_audit_logs.success',
        ], 'created_at', 'desc', 'admin_audit_logs.id');

        $logs = $query->paginate($perPage)->withQueryString();

        $actions = AdminAuditLog::query()->distinct()->orderBy('action')->pluck('action');

        $admins = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['owner', 'admin', 'support']))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'actions' => $actions,
            'admins' => $admins,
            'filters' => array_merge(
                $request->only(['action', 'admin_id', 'success', 'from_date', 'to_date']),
                SortableTable::filters($request, $sort, $perPage),
            ),
        ]);
    }

    public function show(AdminAuditLog $auditLog)
    {
        return Inertia::render('Admin/AuditLogs/Show', [
            'log' => $auditLog->load('admin'),
        ]);
    }
}
