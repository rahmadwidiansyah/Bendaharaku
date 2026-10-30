<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\SortableTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AiLogController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('activity_logs');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->input('log_name'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('description', 'ilike', "%{$search}%")
                    ->orWhere('log_name', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('from_date')) {
            $request->validate(['from_date' => ['date']]);
            $query->where('created_at', '>=', $request->date('from_date')->startOfDay());
        }

        if ($request->filled('to_date')) {
            $request->validate(['to_date' => ['date']]);
            $query->where('created_at', '<=', $request->date('to_date')->endOfDay());
        }

        $perPage = SortableTable::perPage($request, 20);

        $sort = SortableTable::apply($query, $request, [
            'created_at' => 'created_at',
            'status' => 'status',
            'description' => 'description',
            'log_name' => 'log_name',
        ], 'created_at', 'desc', 'id');

        $logs = $query->paginate($perPage)->withQueryString();

        $logNames = DB::table('activity_logs')
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        return Inertia::render('Admin/AiLogs/Index', [
            'logs' => $logs,
            'logNames' => $logNames,
            'filters' => array_merge(
                $request->only(['status', 'log_name', 'search', 'from_date', 'to_date']),
                SortableTable::filters($request, $sort, $perPage),
            ),
        ]);
    }

    public function show($id)
    {
        $log = DB::table('activity_logs')->where('id', $id)->first();

        if (! $log) {
            abort(404, 'Log not found');
        }

        return Inertia::render('Admin/AiLogs/Show', [
            'log' => $log,
        ]);
    }
}
