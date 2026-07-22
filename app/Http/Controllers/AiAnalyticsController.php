<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiDailyMetric;
use App\Models\AiFeedbackLog;
use App\Models\AiParseLog;
use App\Models\UserAiMemory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAnalyticsController extends Controller
{
    // Estimasi harga per 1 Juta Token (USD) — sama dengan AggregateAiMetricsCommand
    private const RATES = [
        'openai' => ['prompt' => 0.150, 'completion' => 0.600],
        'gemini' => ['prompt' => 0.075, 'completion' => 0.300],
        'deepseek' => ['prompt' => 0.140, 'completion' => 0.280],
    ];

    public function dashboard(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $today = Carbon::today()->toDateString();

        // ─────────────────────────────────────────────────────────────
        // 1. Data historis (sebelum hari ini) dari AiDailyMetric
        // ─────────────────────────────────────────────────────────────
        $metrics = AiDailyMetric::where('user_id', $userId)
            ->where('date', '<', $today)
            ->orderBy('date', 'desc')
            ->limit(13) // 13 hari historis + 1 hari realtime = 14 hari
            ->get();

        // ─────────────────────────────────────────────────────────────
        // 2. Data realtime hari ini langsung dari AiParseLog
        // ─────────────────────────────────────────────────────────────
        $todayLogs = AiParseLog::where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->select('provider', 'is_success', 'status', 'raw_confidence',
                'final_confidence', 'prompt_tokens', 'completion_tokens', 'total_tokens')
            ->get();

        // Buat pseudo-metric hari ini dari AiParseLog
        $todayMetric = null;
        if ($todayLogs->isNotEmpty()) {
            $todayByProvider = $todayLogs->groupBy('provider')->map(function ($group, $provider) use ($today) {
                $totalReq = $group->count();
                $totalOk = $group->where('is_success', true)->count();
                $totalDraft = $group->where('status', 'draft')->count();
                $promptTok = $group->sum('prompt_tokens');
                $compTok = $group->sum('completion_tokens');
                $totalTok = $group->sum('total_tokens');

                $provKey = strtolower($provider);
                $promptCost = isset(self::RATES[$provKey]) ? ($promptTok / 1_000_000) * self::RATES[$provKey]['prompt'] : 0;
                $compCost = isset(self::RATES[$provKey]) ? ($compTok / 1_000_000) * self::RATES[$provKey]['completion'] : 0;

                return (object) [
                    'date' => $today,
                    'provider' => $provider,
                    'total_requests' => $totalReq,
                    'total_success' => $totalOk,
                    'total_drafts' => $totalDraft,
                    'total_corrections' => 0,
                    'avg_raw_confidence' => $group->avg('raw_confidence') ?? 0,
                    'avg_final_confidence' => $group->avg('final_confidence') ?? 0,
                    'prompt_tokens' => $promptTok,
                    'completion_tokens' => $compTok,
                    'total_tokens' => $totalTok,
                    'estimated_cost_usd' => round($promptCost + $compCost, 6),
                ];
            })->values(); // Collection of pseudo-metric objects per provider

            $todayMetric = (object) [
                'date' => $today,
                'provider' => null,    // gabungan semua provider
                'total_requests' => $todayLogs->count(),
                'total_success' => $todayLogs->where('is_success', true)->count(),
                'total_drafts' => $todayLogs->where('status', 'draft')->count(),
                'total_corrections' => 0,
                'avg_raw_confidence' => $todayLogs->avg('raw_confidence') ?? 0,
                'avg_final_confidence' => $todayLogs->avg('final_confidence') ?? 0,
                'prompt_tokens' => $todayLogs->sum('prompt_tokens'),
                'completion_tokens' => $todayLogs->sum('completion_tokens'),
                'total_tokens' => $todayLogs->sum('total_tokens'),
                'estimated_cost_usd' => $todayByProvider->sum('estimated_cost_usd'),
                '_by_provider' => $todayByProvider, // breakdown per provider hari ini
            ];
        }

        // ─────────────────────────────────────────────────────────────
        // 3. Gabung untuk overview
        // ─────────────────────────────────────────────────────────────
        $allRequests = $metrics->sum('total_requests') + ($todayMetric?->total_requests ?? 0);
        $allSuccess = $metrics->sum('total_success') + ($todayMetric?->total_success ?? 0);
        $allDrafts = $metrics->sum('total_drafts') + ($todayMetric?->total_drafts ?? 0);
        $allCorrections = $metrics->sum('total_corrections');
        $allTokens = $metrics->sum('total_tokens') + ($todayMetric?->total_tokens ?? 0);
        $allCost = (float) $metrics->sum('estimated_cost_usd') + ($todayMetric?->estimated_cost_usd ?? 0);

        $overview = [
            'total_requests' => $allRequests,
            'total_drafts' => $allDrafts,
            'total_tokens' => $allTokens,
            'estimated_cost' => round($allCost, 6),
            'success_rate' => $allRequests > 0 ? round(($allSuccess / $allRequests) * 100, 1) : 0,
            'draft_rate' => $allRequests > 0 ? round(($allDrafts / $allRequests) * 100, 1) : 0,
            'correction_rate' => $allRequests > 0 ? round(($allCorrections / $allRequests) * 100, 1) : 0,
        ];

        // ─────────────────────────────────────────────────────────────
        // 4. Breakdown per provider
        //    Gabung dari historis + hari ini
        // ─────────────────────────────────────────────────────────────
        $providers = $metrics
            ->groupBy('provider')
            ->map(fn ($group) => $group->sum('total_requests'));

        // Tambahkan data hari ini per provider
        if ($todayMetric && isset($todayMetric->_by_provider)) {
            foreach ($todayMetric->_by_provider as $pm) {
                $key = $pm->provider;
                $providers[$key] = ($providers[$key] ?? 0) + $pm->total_requests;
            }
        }

        // ─────────────────────────────────────────────────────────────
        // 5. Trend harian — historis + hari ini
        // ─────────────────────────────────────────────────────────────
        $trendFromMetrics = $metrics
            ->sortBy('date')
            ->values()
            ->groupBy('date') // aggregate per date (bisa multi-provider per hari)
            ->map(function ($dayGroup, $date) {
                $totalReq = $dayGroup->sum('total_requests');

                return [
                    'date' => Carbon::parse($date)->format('d M'),
                    'raw_conf' => $totalReq > 0
                        ? round($dayGroup->sum(fn ($m) => $m->avg_raw_confidence * $m->total_requests) / $totalReq, 4)
                        : 0,
                    'final_conf' => $totalReq > 0
                        ? round($dayGroup->sum(fn ($m) => $m->avg_final_confidence * $m->total_requests) / $totalReq, 4)
                        : 0,
                    'requests' => $totalReq,
                ];
            })
            ->values();

        // Tambahkan hari ini di akhir
        if ($todayMetric) {
            $trendFromMetrics->push([
                'date' => Carbon::today()->format('d M'),
                'raw_conf' => round((float) $todayMetric->avg_raw_confidence, 4),
                'final_conf' => round((float) $todayMetric->avg_final_confidence, 4),
                'requests' => $todayMetric->total_requests,
            ]);
        }

        return response()->json([
            'overview' => $overview,
            'providers' => $providers,
            'performance' => $trendFromMetrics,
        ]);
    }

    public function feedback(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Top memori terbentuk dari koreksi user
        $topMemories = UserAiMemory::where('user_id', $userId)
            ->orderByDesc('effective_weight')
            ->limit(10)
            ->get(['keyword_pattern', 'hit_count', 'effective_weight']);

        // Kategori yang paling sering dikoreksi
        $topCorrectedCategories = AiFeedbackLog::where('user_id', $userId)
            ->selectRaw("corrected_payload->>'category_id' as category_id, COUNT(*) as count")
            ->whereNotNull('corrected_payload')
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return response()->json([
            'learning' => [
                'top_memories' => $topMemories,
                'top_corrected_categories' => $topCorrectedCategories,
            ],
        ]);
    }
}
