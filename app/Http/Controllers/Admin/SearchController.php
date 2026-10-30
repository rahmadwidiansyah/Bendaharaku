<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pencarian global (Ctrl/Cmd+K) untuk shell admin.
 *
 * Endpoint ini hanya mengembalikan data yang boleh dilihat oleh admin yang
 * sedang login. Jangan menambahkan data sensitif (token, saldo, dsb.) di sini.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $term = trim($validated['q']);
        $user = $request->user();
        $results = [];

        if ($user->can('users.view')) {
            $results['users'] = User::query()
                ->where(function ($query) use ($term) {
                    $query->where('name', 'ilike', "%{$term}%")
                        ->orWhere('email', 'ilike', "%{$term}%");
                })
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name', 'email', 'status', 'last_active_at'])
                ->map(fn (User $result) => [
                    'id' => $result->id,
                    'name' => $result->name,
                    'email' => $result->email,
                    'status' => $result->status ?? 'active',
                    'last_active_at' => $result->last_active_at?->toIso8601String(),
                    'href' => route('admin.users.show', $result->id),
                ])
                ->all();
        }

        if ($user->can('audit_logs.view')) {
            $results['logs'] = AdminAuditLog::query()
                ->where(function ($query) use ($term) {
                    $query->where('action', 'ilike', "%{$term}%")
                        ->orWhere('target_type', 'ilike', "%{$term}%");
                })
                ->latest()
                ->limit(4)
                ->get(['id', 'action', 'target_type', 'target_id', 'created_at'])
                ->map(fn (AdminAuditLog $log) => [
                    'id' => $log->id,
                    'label' => Str::headline($log->action),
                    'meta' => trim(($log->target_type ?? '').' #'.($log->target_id ?? '')),
                    'created_at' => $log->created_at?->toIso8601String(),
                    'href' => route('admin.audit_logs.show', $log->id),
                ])
                ->all();
        }

        return response()->json([
            'query' => $term,
            'groups' => $results,
        ]);
    }
}
