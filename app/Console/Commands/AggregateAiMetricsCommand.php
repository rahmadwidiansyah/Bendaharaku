<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateAiMetricsCommand extends Command
{
    protected $signature = 'ai:aggregate-metrics {--date= : Tanggal agregasi (Y-m-d), default kemarin}';

    protected $description = 'Melakukan agregasi log parse AI ke tabel daily metrics.';

    // Estimasi harga per 1 Juta Token (USD) [Contoh Rate 2026]
    private const RATES = [
        'openai' => ['prompt' => 0.150, 'completion' => 0.600], // gpt-4o-mini
        'gemini' => ['prompt' => 0.075, 'completion' => 0.300], // 2.5-flash
        'deepseek' => ['prompt' => 0.140, 'completion' => 0.280], // deepseek-chat
    ];

    public function handle(): int
    {
        $targetDate = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
        $dateString = $targetDate->format('Y-m-d');

        $this->info("Memulai agregasi metrik AI untuk tanggal: {$dateString}");

        // Eksekusi Agregasi menggunakan Subquery dan Group By di level DB (Sangat Cepat)
        $aggregates = DB::table('ai_parse_logs as p')
            ->leftJoin('ai_feedback_logs as f', 'p.id', '=', 'f.parse_log_id')
            ->whereDate('p.created_at', $dateString)
            ->select(
                'p.user_id',
                'p.provider',
                DB::raw('COUNT(p.id) as total_requests'),
                DB::raw('SUM(CASE WHEN p.is_success = 1 THEN 1 ELSE 0 END) as total_success'),
                DB::raw('SUM(CASE WHEN p.status = "draft" THEN 1 ELSE 0 END) as total_drafts'),
                DB::raw('COUNT(f.id) as total_corrections'),
                DB::raw('AVG(p.raw_confidence) as avg_raw_confidence'),
                DB::raw('AVG(p.final_confidence) as avg_final_confidence'),
                DB::raw('SUM(p.prompt_tokens) as prompt_tokens'),
                DB::raw('SUM(p.completion_tokens) as completion_tokens'),
                DB::raw('SUM(p.total_tokens) as total_tokens')
            )
            ->groupBy('p.user_id', 'p.provider')
            ->get();

        $upsertData = [];
        foreach ($aggregates as $row) {
            $providerKey = strtolower($row->provider);
            $promptCost = isset(self::RATES[$providerKey]) ? ($row->prompt_tokens / 1000000) * self::RATES[$providerKey]['prompt'] : 0;
            $compCost = isset(self::RATES[$providerKey]) ? ($row->completion_tokens / 1000000) * self::RATES[$providerKey]['completion'] : 0;

            $upsertData[] = [
                'user_id' => $row->user_id,
                'date' => $dateString,
                'provider' => $row->provider,
                'total_requests' => $row->total_requests,
                'total_success' => $row->total_success,
                'total_drafts' => $row->total_drafts,
                'total_corrections' => $row->total_corrections,
                'avg_raw_confidence' => round((float) $row->avg_raw_confidence, 4),
                'avg_final_confidence' => round((float) $row->avg_final_confidence, 4),
                'prompt_tokens' => $row->prompt_tokens,
                'completion_tokens' => $row->completion_tokens,
                'total_tokens' => $row->total_tokens,
                'estimated_cost_usd' => round($promptCost + $compCost, 6),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($upsertData)) {
            DB::table('ai_daily_metrics')->upsert(
                $upsertData,
                ['user_id', 'date', 'provider'], // Unique keys
                ['total_requests', 'total_success', 'total_drafts', 'total_corrections', 'avg_raw_confidence', 'avg_final_confidence', 'prompt_tokens', 'completion_tokens', 'total_tokens', 'estimated_cost_usd', 'updated_at'] // Update columns
            );
        }

        $this->info('Berhasil memproses '.count($upsertData).' baris agregasi.');

        return self::SUCCESS;
    }
}
