<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AiParseLog;
use App\Models\AiUsageLog;
use App\Models\UserAiMemory;
use App\Models\UserAiMemoryLog;
use App\Services\AI\AiAccountManager;
use App\Services\AI\Memory\MemoryDecayEngine;
use App\Services\AI\Routing\ModelResolver;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AiSettingsController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        // 1. Ambil Statistik Penggunaan Token (Di-group per provider)
        $usageStats = AiUsageLog::where('user_id', $user->id)
            ->selectRaw('provider, SUM(prompt_tokens) as total_prompt, SUM(completion_tokens) as total_completion, SUM(total_tokens) as total_used')
            ->groupBy('provider')
            ->get()
            ->keyBy('provider');

        // 2. Ambil 10 Riwayat Log Terakhir
        $recentLogs = AiParseLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                // Gunakan kolom status langsung dari database
                // status bisa: 'draft', 'executed', 'failed', 'rate_limit', 'timeout', dll.
                $rawStatus = strtolower((string) ($log->status ?? ''));

                $status = match (true) {
                    $rawStatus === 'draft' => 'Draft',
                    in_array($rawStatus, ['executed', 'success', ''])
                        && $log->is_success => 'Executed',
                    str_contains($rawStatus, 'rate') || str_contains($rawStatus, 'quota') => 'Rate Limit',
                    str_contains($rawStatus, 'timeout') => 'Timeout',
                    ! $log->is_success => 'Failed',
                    default => ucfirst($rawStatus) ?: 'Executed',
                };

                return [
                    'id' => $log->id,
                    'provider' => strtoupper($log->provider ?? 'PYTHON-NLP'),
                    'input_text' => $log->input_text,
                    'confidence' => $log->final_confidence ? round($log->final_confidence * 100) : null,
                    'status' => $status,
                    'error' => $log->error_message,
                    'date' => $log->created_at->diffForHumans(),
                ];
            });

        // Pool baru: akun multi-key + katalog discovery + routing (tanpa secret).
        $accountManager = app(AiAccountManager::class);
        $routing = $accountManager->getOrCreateRouting($user);
        $resolved = app(ModelResolver::class)->resolve($user, $routing);

        return Inertia::render('Settings/AI/Models', [
            'usageStats' => $usageStats,
            'recentLogs' => $recentLogs,
            'aiAccounts' => $accountManager->listFor($user),
            'aiRouting' => $routing,
            'aiCandidates' => array_map(fn ($c) => [
                'provider' => $c['provider']->value ?? (string) $c['provider'],
                'model' => $c['model_id'],
                'account_id' => $c['account']->id,
                'account_alias' => $c['account']->alias ?? null,
                'source' => $c['source'] ?? null,
            ], array_slice($resolved['candidates'], 0, 8)),
            'aiSkipped' => array_slice($resolved['skipped'] ?? [], 0, 20),
        ]);
    }

    public function memories(Request $request): Response
    {
        $user = $request->user();
        $search = $request->input('search');
        $filter = $request->input('filter', 'all');

        $query = UserAiMemory::where('user_id', $user->id)
            ->with(['category:id,category_name', 'wallet:id,name']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('keyword_pattern', 'like', "%{$search}%")
                    ->orWhere('raw_subject', 'like', "%{$search}%")
                    ->orWhere('normalized_subject', 'like', "%{$search}%")
                    ->orWhere('memory_keyword', 'like', "%{$search}%");
            });
        }

        if ($filter === 'active') {
            $query->where('weight', '>', 0.0);
        } elseif ($filter === 'low') {
            $query->where('weight', '<', 1.0);
        } elseif ($filter === 'high') {
            $query->where('weight', '>=', 3.0);
        }

        $memories = $query->orderByDesc('weight')
            ->orderByDesc('hit_count')
            ->paginate(20)
            ->through(fn (UserAiMemory $m) => [
                'id' => $m->id,
                'keyword' => $m->memory_keyword ?? $m->keyword_pattern,
                'raw_subject' => $m->raw_subject,
                'normalized_subject' => $m->normalized_subject,
                'category' => $m->category?->category_name,
                'wallet' => $m->wallet?->name,
                'weight' => (float) $m->weight,
                'hit_count' => $m->hit_count,
                'last_applied_at' => $m->last_applied_at?->diffForHumans(),
                'created_at' => $m->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Settings/AI/MemoryManage', [
            'memories' => $memories,
            'filters' => ['search' => $search, 'filter' => $filter],
        ]);
    }

    public function memoryLogs(int $memoryId, Request $request): Response
    {
        $user = $request->user();

        $memory = UserAiMemory::where('user_id', $user->id)
            ->with(['category:id,category_name', 'wallet:id,name'])
            ->withCount('activeContributions')
            ->findOrFail($memoryId);

        // Hitung effective weight via decay
        $effectiveWeight = $memory->weight;
        $daysSince = null;
        try {
            $effectiveWeight = app(MemoryDecayEngine::class)->calculateDecayedWeight((float) $memory->weight, $memory->last_applied_at ?? $memory->created_at);
            $ref = $memory->last_applied_at ?? $memory->created_at;
            if ($ref) {
                $daysSince = (int) $ref->diffInDays(now());
            }
        } catch (\Throwable $e) {
        }

        $contributions = $memory->activeContributions()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'transaction_id' => $c->transaction_id,
                'source' => $c->source,
                'keyword' => $c->keyword,
                'target_type' => $c->target_type,
                'target_name' => $c->target_name,
                'weight_delta' => (float) $c->weight_delta,
                'is_active' => (bool) $c->is_active,
                'created_at' => $c->created_at->toIso8601String(),
                'created_at_diff' => $c->created_at->diffForHumans(),
            ]);

        $logs = UserAiMemoryLog::where('memory_id', $memoryId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (UserAiMemoryLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'source' => $log->source,
                'raw_subject' => $log->raw_subject,
                'normalized_subject' => $log->normalized_subject,
                'memory_keyword' => $log->memory_keyword,
                'old_weight' => $log->old_weight,
                'new_weight' => $log->new_weight,
                'old_hit_count' => $log->old_hit_count,
                'new_hit_count' => $log->new_hit_count,
                'reason' => $log->reason,
                'metadata' => $log->metadata,
                'algorithm_version' => $log->algorithm_version,
                'transaction_id' => $log->transaction_id,
                'created_at' => $log->created_at->toIso8601String(),
                'created_at_diff' => $log->created_at->diffForHumans(),
            ]);

        return Inertia::render('Settings/AI/MemoryDetail', [
            'memory' => [
                'id' => $memory->id,
                'keyword' => $memory->memory_keyword ?? $memory->keyword_pattern,
                'keyword_pattern' => $memory->keyword_pattern,
                'memory_keyword' => $memory->memory_keyword,
                'raw_subject' => $memory->raw_subject,
                'normalized_subject' => $memory->normalized_subject,
                'target_type' => $memory->target_type,
                'category' => $memory->category?->category_name,
                'wallet' => $memory->wallet?->name,
                'weight' => (float) $memory->weight,
                'effective_weight' => (float) $effectiveWeight,
                'hit_count' => $memory->hit_count,
                'contributions_count' => $memory->active_contributions_count,
                'contributions' => $contributions,
                'days_since' => $daysSince,
                'last_applied_at' => $memory->last_applied_at?->diffForHumans(),
                'last_applied_at_raw' => $memory->last_applied_at?->toIso8601String(),
                'created_at' => $memory->created_at?->diffForHumans(),
                'created_at_raw' => $memory->created_at?->toIso8601String(),
                'algorithm_version' => 'v2-provenance',
            ],
            'logs' => $logs,
        ]);
    }

    public function destroy(int $memoryId, Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $memory = UserAiMemory::where('user_id', $user->id)->findOrFail($memoryId);

        DB::transaction(function () use ($memory, $user) {
            try {
                UserAiMemoryLog::create([
                    'memory_id' => $memory->id,
                    'user_id' => $user->id,
                    'action' => 'DELETED',
                    'raw_subject' => $memory->raw_subject,
                    'normalized_subject' => $memory->normalized_subject,
                    'memory_keyword' => $memory->memory_keyword ?? $memory->keyword_pattern,
                    'old_weight' => $memory->weight,
                    'new_weight' => null,
                    'old_hit_count' => $memory->hit_count,
                    'new_hit_count' => null,
                    'reason' => 'User deleted via settings',
                    'metadata' => ['source' => 'user_delete', 'keyword' => $memory->memory_keyword ?? $memory->keyword_pattern, 'target_type' => $memory->target_type],
                    'algorithm_version' => 'v2-provenance',
                ]);
            } catch (\Throwable $e) {
            }

            $memory->delete();
        });

        try {
            Cache::forget("ai-mem-v2-{$user->id}");
            Cache::forget("ai-mem-resolve-{$user->id}");
        } catch (\Throwable $e) {
        }

        ActivityLogger::forUser($user, 'ai_memory', 'deleted', 'Memory dihapus: '.($memory->memory_keyword ?? $memory->keyword_pattern), null, ['memory_id' => $memoryId]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('settings.ai.memory.manage')->with('success', __('settings.ai.memory.deleted'));
    }
}
