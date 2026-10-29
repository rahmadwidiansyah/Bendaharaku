<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminAuditLog::with('admin');

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // Filter by admin
        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->input('admin_id'));
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date'));
        }

        $per_page = $request->input('per_page', 20);
        $logs = $query->latest('created_at')->paginate($per_page);

        // Get unique actions for filter dropdown
        $actions = AdminAuditLog::distinct('action')->pluck('action');

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'actions' => $actions,
            'filters' => $request->only(['action', 'admin_id', 'from_date', 'to_date', 'per_page']),
        ]);
    }

    public function show(AdminAuditLog $auditLog)
    {
        return Inertia::render('Admin/AuditLogs/Show', [
            'log' => $auditLog->load('admin'),
        ]);
    }
}
