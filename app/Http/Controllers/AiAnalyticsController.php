<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiDailyMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AiAnalyticsController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        // 1. Ambil agregasi 14 hari terakhir milik user tersebut
        $metrics = AiDailyMetric::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->limit(14)
            ->get();
        
        $totalParse = $metrics->sum('total_requests');
        
        $overview = [
            'total_requests' => $totalParse,
            'total_drafts'   => $metrics->sum('total_drafts'),
            'total_tokens'   => $metrics->sum('total_tokens'),
            // Kalkulasi rata-rata tertimbang (weighted average) dari database
            'success_rate'   => $totalParse > 0 ? round(($metrics->sum('total_success') / $totalParse) * 100, 1) : 0,
            'draft_rate'     => $totalParse > 0 ? round(($metrics->sum('total_drafts') / $totalParse) * 100, 1) : 0,
        ];

        // Format data untuk Chart.js di Vue
        $performanceTrend = $metrics->map(fn($m) => [
            'date' => Carbon::parse($m->date)->format('d M'),
            'final_conf' => $m->avg_final_confidence, // Dalam desimal 0.0 - 1.0
            'requests' => $m->total_requests
        ])->reverse()->values();

        return response()->json([
            'overview' => $overview,
            'performance' => $performanceTrend
        ]);
    }
}