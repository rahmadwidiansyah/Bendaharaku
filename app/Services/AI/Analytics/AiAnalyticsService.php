<?php

declare(strict_types=1);

namespace App\Services\AI\Analytics;

use App\Models\AiDailyMetric;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AiAnalyticsService
{
    public function getOverview(int $userId, string $startDate, string $endDate): array
    {
        $metrics = AiDailyMetric::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                SUM(total_requests) as requests,
                SUM(total_success) as success,
                SUM(total_drafts) as drafts,
                SUM(total_corrections) as corrections,
                SUM(total_tokens) as tokens,
                SUM(estimated_cost_usd) as cost
            ')->first();

        $requests = (int) $metrics->requests;

        return [
            'total_requests'  => $requests,
            'success_rate'    => $requests > 0 ? round(($metrics->success / $requests) * 100, 2) : 0,
            'draft_rate'      => $requests > 0 ? round(($metrics->drafts / $requests) * 100, 2) : 0,
            'correction_rate' => $requests > 0 ? round(($metrics->corrections / $requests) * 100, 2) : 0,
            'total_tokens'    => (int) $metrics->tokens,
            'estimated_cost'  => (float) $metrics->cost,
        ];
    }

    public function getProviderDistribution(int $userId, string $startDate, string $endDate): array
    {
        return AiDailyMetric::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('provider, SUM(total_requests) as value')
            ->groupBy('provider')
            ->pluck('value', 'provider')
            ->toArray();
    }

    public function getPerformanceAnalytics(int $userId, string $startDate, string $endDate): array
    {
        return AiDailyMetric::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                date, 
                AVG(avg_raw_confidence) as raw_conf, 
                AVG(avg_final_confidence) as final_conf
            ')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->toArray();
    }

    public function getLearningAnalytics(int $userId): array
    {
        // Top Memori (Keywords yang paling sering dipelajari AI dari user ini)
        $topMemories = DB::table('user_ai_memories')
            ->where('user_id', $userId)
            ->orderByDesc('hit_count')
            ->limit(5)
            ->get(['keyword_pattern', 'hit_count', 'weight']);

        // Insight Kategori yang sering salah tebak dan dikoreksi
        $topCategories = DB::table('ai_feedback_logs')
            ->where('user_id', $userId)
            ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(corrected_payload, '$.category_id')) as category_id"), DB::raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return [
            'top_memories' => $topMemories,
            'top_corrected_categories' => $topCategories,
        ];
    }
}