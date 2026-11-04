<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\SortableTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * AiToolLogController — pembaca audit trail eksekusi tool assistant
 * (tabel ai_tool_logs). Terpisah dari AiLogController yang membaca
 * activity_logs (jejak operasi provider AI).
 */
class AiToolLogController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('ai_tool_logs')
            ->leftJoin('users', 'users.id', '=', 'ai_tool_logs.user_id')
            ->select(
                'ai_tool_logs.*',
                'users.email as user_email',
                'users.name as user_name',
            );

        if ($request->filled('tool')) {
            $query->where('ai_tool_logs.tool', $request->input('tool'));
        }

        if ($request->filled('status')) {
            $query->where('ai_tool_logs.status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('ai_tool_logs.trace_id', 'ilike', "%{$search}%")
                    ->orWhere('users.email', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('from_date')) {
            $request->validate(['from_date' => ['date']]);
            $query->where('ai_tool_logs.created_at', '>=', $request->date('from_date')->startOfDay());
        }

        if ($request->filled('to_date')) {
            $request->validate(['to_date' => ['date']]);
            $query->where('ai_tool_logs.created_at', '<=', $request->date('to_date')->endOfDay());
        }

        $perPage = SortableTable::perPage($request, 20);

        $sort = SortableTable::apply($query, $request, [
            'tool' => 'ai_tool_logs.tool',
            'status' => 'ai_tool_logs.status',
            'duration_ms' => 'ai_tool_logs.duration_ms',
            'result_rows' => 'ai_tool_logs.result_rows',
            'created_at' => 'ai_tool_logs.created_at',
        ], 'created_at', 'desc', 'ai_tool_logs.id');

        $logs = $query->paginate($perPage)->withQueryString();

        $tools = DB::table('ai_tool_logs')
            ->distinct()
            ->orderBy('tool')
            ->pluck('tool');

        return Inertia::render('Admin/AiToolLogs/Index', [
            'logs' => $logs,
            'tools' => $tools,
            'filters' => array_merge(
                $request->only(['tool', 'status', 'search', 'from_date', 'to_date']),
                SortableTable::filters($request, $sort, $perPage),
            ),
        ]);
    }

    public function show($id)
    {
        $log = DB::table('ai_tool_logs')
            ->leftJoin('users', 'users.id', '=', 'ai_tool_logs.user_id')
            ->where('ai_tool_logs.id', $id)
            ->select(
                'ai_tool_logs.*',
                'users.email as user_email',
                'users.name as user_name',
            )
            ->first();

        if (! $log) {
            abort(404, 'Tool log not found');
        }

        // Params adalah argumen tool dari LLM (tanggal, id, limit) — bukan
        // secret. Decode agar bisa dirender sebagai JSON rapi di frontend.
        $log->params = json_decode((string) $log->params, true);

        return Inertia::render('Admin/AiToolLogs/Show', [
            'log' => $log,
        ]);
    }
}
