<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AiLogController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('activity_logs')
            ->latest('created_at');

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date'));
        }

        $per_page = $request->input('per_page', 20);
        $logs = $query->paginate($per_page);

        return Inertia::render('Admin/AiLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['status', 'from_date', 'to_date', 'per_page']),
        ]);
    }

    public function show($id)
    {
        $log = DB::table('activity_logs')->where('id', $id)->first();

        if (!$log) {
            abort(404, 'Log not found');
        }

        return Inertia::render('Admin/AiLogs/Show', [
            'log' => $log,
        ]);
    }
}
