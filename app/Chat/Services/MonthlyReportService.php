<?php

declare(strict_types=1);

namespace App\Chat\Services;

use App\Chat\Components\DividerComponent;
use App\Chat\Components\ReportSectionComponent;
use App\Chat\Components\TextComponent;
use App\Chat\Components\WarningComponent;
use App\Chat\DTOs\ChatResponse;
use App\Enums\AiProvider;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Models\UserAiCredential;
use App\Support\MoneyFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MonthlyReportService
{
    public function __construct(
        private readonly AiReportClient $aiReportClient,
    ) {}

    public function buildMonthlyReportResponse(User $user, array $metadata, string $rawText): ChatResponse
    {
        $period = $this->resolveReportPeriod($rawText);
        $monthStart = $period->copy()->startOfMonth();
        $monthEnd = $period->copy()->endOfMonth();
        $periodKey = $monthStart->toDateString();

        $previousMonthStart = $monthStart->copy()->subMonthNoOverflow()->startOfMonth();
        $this->ensureMonthlyReportExists($user, $previousMonthStart);

        $previousReport = MonthlyReport::where('user_id', $user->id)
            ->whereDate('period_month', $previousMonthStart->toDateString())
            ->first();

        $transactions = $user->transactionLogs()
            ->with(['type', 'category', 'sourceWallet', 'destinationWallet'])
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        if ($transactions->isEmpty()) {
            return ChatResponse::command([
                new TextComponent(
                    translationKey: 'chat.command.report_empty_period',
                    params: ['period' => $monthStart->translatedFormat('F Y')],
                ),
            ], $metadata);
        }

        $metrics = $this->buildMonthlyMetrics($transactions);
        $comparisonMetrics = $this->buildComparisonMetrics($metrics, $previousReport);
        $localReport = $this->buildLocalMonthlyReport($transactions, $monthStart, $previousReport);
        $geminiResult = $this->generateGeminiMonthlyReport($user, $transactions, $localReport, $monthStart, $previousReport);
        $geminiReport = $geminiResult['summary'] ?? null;
        $finalReport = $geminiReport ?? $localReport;

        MonthlyReport::updateOrCreate(
            [
                'user_id' => $user->id,
                'period_month' => $periodKey,
            ],
            [
                'title' => 'Laporan '.$monthStart->translatedFormat('F Y'),
                'local_summary' => $localReport,
                'ai_summary' => $geminiReport,
                'final_summary' => $finalReport,
                'metrics' => $metrics,
                'previous_month_summary' => $previousReport?->final_summary,
                'comparison_metrics' => $comparisonMetrics,
                'provider' => $geminiReport ? 'gemini' : 'local',
                'model' => $geminiResult['model'] ?? null,
                'status' => 'completed',
            ],
        );

        $components = $this->buildReportComponents(
            monthStart: $monthStart,
            finalReport: $finalReport,
            comparisonMetrics: $comparisonMetrics,
            geminiReport: $geminiReport,
        );

        return ChatResponse::command($components, $metadata);
    }

    public function buildMonthlyMetrics($transactions): Collection
    {
        $transactions = Collection::make($transactions);

        $income = (float) $transactions
            ->filter(fn ($trx) => strtolower($trx->type?->name ?? '') === 'income')
            ->sum('amount');
        $expense = (float) $transactions
            ->filter(fn ($trx) => strtolower($trx->type?->name ?? '') === 'expense')
            ->sum('amount');

        return Collection::make([
            'transaction_count' => $transactions->count(),
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
            'top_expense_categories' => $transactions
                ->filter(fn ($trx) => strtolower($trx->type?->name ?? '') === 'expense')
                ->groupBy(fn ($trx) => $trx->category?->category_name ?? '-')
                ->map(fn ($items) => (float) $items->sum('amount'))
                ->sortDesc()
                ->take(5)
                ->toArray(),
        ]);
    }

    public function ensureMonthlyReportExists(User $user, Carbon $monthStart): void
    {
        $monthEnd = $monthStart->copy()->endOfMonth();
        $periodKey = $monthStart->toDateString();

        $existing = MonthlyReport::where('user_id', $user->id)
            ->whereDate('period_month', $periodKey)
            ->first();

        if ($existing) {
            return;
        }

        $transactions = $user->transactionLogs()
            ->with(['type', 'category', 'sourceWallet', 'destinationWallet'])
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        if ($transactions->isEmpty()) {
            return;
        }

        $metrics = $this->buildMonthlyMetrics($transactions);
        $localReport = $this->buildLocalMonthlyReport($transactions, $monthStart, null);

        $geminiResult = $this->generateGeminiMonthlyReport($user, $transactions, $localReport, $monthStart, null);
        $geminiReport = $geminiResult['summary'] ?? null;
        $finalReport = $geminiReport ?? $localReport;

        MonthlyReport::updateOrCreate(
            [
                'user_id' => $user->id,
                'period_month' => $periodKey,
            ],
            [
                'summary' => $finalReport,
                'metrics' => $metrics,
                'status' => 'completed',
            ]
        );
    }

    private function buildReportComponents(
        Carbon $monthStart,
        ?string $finalReport,
        mixed $comparisonMetrics,
        ?string $geminiReport,
    ): array {
        $components = [
            new ReportSectionComponent(
                title: $monthStart->translatedFormat('F Y'),
                emoji: '📊',
                translationKey: 'chat.command.report_title_period',
            ),
        ];

        if ($finalReport) {
            $components[] = new TextComponent(
                translationKey: 'chat.command.report_summary',
                params: ['summary' => $finalReport],
            );
        }

        if ($comparisonMetrics && ! empty($comparisonMetrics)) {
            $components = array_merge($components, $this->buildComparisonComponents($comparisonMetrics));
        }

        $components[] = new DividerComponent;
        $components[] = new TextComponent(translationKey: 'chat.command.report_saved');

        if ($geminiReport === null) {
            $components[] = new WarningComponent(
                messageKey: 'chat.command.report_gemini_unavailable',
            );
        }

        return $components;
    }

    private function buildComparisonComponents(array $comparisonMetrics): array
    {
        $components = [new DividerComponent];
        $items = [];

        if (isset($comparisonMetrics['income_diff'])) {
            $emoji = match ($comparisonMetrics['income_trend'] ?? 'stable') {
                'up' => '📈',
                'down' => '📉',
                default => '➡️',
            };
            $items[] = __('chat.command.report_comparison_income', [
                'emoji' => $emoji,
                'amount' => $this->formatCurrency($comparisonMetrics['income_diff']),
            ]);
        }

        if (isset($comparisonMetrics['expense_diff'])) {
            $emoji = match ($comparisonMetrics['expense_trend'] ?? 'stable') {
                'up' => '📈',
                'down' => '📉',
                default => '➡️',
            };
            $items[] = __('chat.command.report_comparison_expense', [
                'emoji' => $emoji,
                'amount' => $this->formatCurrency($comparisonMetrics['expense_diff']),
            ]);
        }

        if (! empty($items)) {
            $components[] = new ReportSectionComponent(
                title: __('chat.command.report_comparison_title'),
                emoji: '📊',
                items: $items,
                translationKey: 'chat.command.report_comparison',
            );
        }

        return $components;
    }

    public function resolveReportPeriod(string $rawText): Carbon
    {
        $text = mb_strtolower(trim($rawText));
        $now = now();

        if (str_contains($text, 'kemarin') || str_contains($text, 'lalu') || str_contains($text, 'sebelumnya')) {
            return $now->copy()->subMonthNoOverflow()->startOfMonth();
        }

        $months = [
            'januari' => 1, 'jan' => 1,
            'februari' => 2, 'feb' => 2,
            'maret' => 3, 'mar' => 3,
            'april' => 4, 'apr' => 4,
            'mei' => 5,
            'juni' => 6, 'jun' => 6,
            'juli' => 7, 'jul' => 7,
            'agustus' => 8, 'agu' => 8, 'ags' => 8,
            'september' => 9, 'sep' => 9,
            'oktober' => 10, 'okt' => 10,
            'november' => 11, 'nov' => 11,
            'desember' => 12, 'des' => 12,
        ];

        foreach ($months as $name => $month) {
            if (! preg_match('/\b'.preg_quote($name, '/').'\b/u', $text)) {
                continue;
            }

            $year = $now->year;
            if (preg_match('/\b(20\d{2})\b/', $text, $match)) {
                $year = (int) $match[1];
            } elseif ($month > $now->month) {
                $year--;
            }

            return Carbon::create($year, $month, 1, 0, 0, 0, $now->timezone)->startOfMonth();
        }

        return $now->copy()->startOfMonth();
    }

    public function formatCurrency(float $amount): string
    {
        $formatted = number_format(abs($amount), 0, ',', '.');

        return ($amount < 0 ? '-' : '+').'Rp '.$formatted;
    }

    public function buildLocalMonthlyReport($transactions, ?Carbon $period = null, ?MonthlyReport $previousReport = null)
    {
        $transactions = Collection::make($transactions);

        $income = (float) $transactions
            ->filter(fn ($trx) => strtolower($trx->type?->name ?? '') === 'income')
            ->sum('amount');
        $expense = (float) $transactions
            ->filter(fn ($trx) => strtolower($trx->type?->name ?? '') === 'expense')
            ->sum('amount');
        $net = $income - $expense;

        $topCategories = $transactions
            ->filter(fn ($trx) => strtolower($trx->type?->name ?? '') === 'expense')
            ->groupBy(fn ($trx) => $trx->category?->category_name ?? '-')
            ->map(fn ($items) => (float) $items->sum('amount'))
            ->sortDesc()
            ->take(5)
            ->map(fn ($amount, $category) => "{$category}: ".MoneyFormatter::rupiah($amount))
            ->values()
            ->join("\n");

        $reportText = implode("\n", array_filter([
            __('chat.command.report_period', ['period' => $period ? $period->translatedFormat('F Y') : now()->translatedFormat('F Y')]),
            __('chat.command.report_income', ['amount' => MoneyFormatter::rupiah($income)]),
            __('chat.command.report_expense', ['amount' => MoneyFormatter::rupiah($expense)]),
            __('chat.command.report_net', ['amount' => MoneyFormatter::rupiah($net)]),
            $previousReport ? __('chat.command.report_previous', ['summary' => $previousReport->final_summary]) : null,
            $topCategories ? __('chat.command.report_top_categories', ['categories' => $topCategories]) : null,
        ]));

        if ($period === null) {
            return [
                'summary' => $reportText,
                'income' => $income,
                'expense' => $expense,
                'net' => $net,
                'top_categories' => $topCategories ? explode("\n", $topCategories) : [],
            ];
        }

        return $reportText;
    }

    public function buildComparisonMetrics(array|Collection $currentMetrics, ?MonthlyReport $previousReport = null): ?array
    {
        if (! $previousReport || ! $previousReport->metrics) {
            return null;
        }

        $prev = $previousReport->metrics;
        $curr = is_array($currentMetrics) ? $currentMetrics : Collection::make($currentMetrics)->toArray();

        return [
            'income_diff' => ($curr['income'] ?? 0) - ($prev['income'] ?? 0),
            'income_diff_percent' => ($prev['income'] ?? 0) > 0
                ? round((($curr['income'] ?? 0) - ($prev['income'] ?? 0)) / ($prev['income'] ?? 0) * 100, 2)
                : 0,
            'expense_diff' => ($curr['expense'] ?? 0) - ($prev['expense'] ?? 0),
            'expense_diff_percent' => ($prev['expense'] ?? 0) > 0
                ? round((($curr['expense'] ?? 0) - ($prev['expense'] ?? 0)) / ($prev['expense'] ?? 0) * 100, 2)
                : 0,
            'net_diff' => ($curr['net'] ?? 0) - ($prev['net'] ?? 0),
            'transaction_count_diff' => ($curr['transaction_count'] ?? 0) - ($prev['transaction_count'] ?? 0),
            'trend' => match (true) {
                ($curr['net'] ?? 0) > ($prev['net'] ?? 0) => 'up',
                ($curr['net'] ?? 0) < ($prev['net'] ?? 0) => 'down',
                default => 'stable',
            },
        ];
    }

    public function generateGeminiMonthlyReport(
        User $user,
        $transactions,
        string $localReport,
        Carbon $period,
        ?MonthlyReport $previousReport = null,
    ): ?array {
        $credential = UserAiCredential::where('user_id', $user->id)
            ->where('provider', AiProvider::Gemini->value)
            ->where('is_valid', true)
            ->first();

        if (! $credential || blank($credential->api_key)) {
            return null;
        }

        $preference = $user->aiPreferences()
            ->where('provider', AiProvider::Gemini->value)
            ->first();
        $model = $preference?->selected_model ?: AiProvider::Gemini->defaultModel();

        $transactionsCollection = Collection::make($transactions);
        $transactionsForPayload = $transactionsCollection->take(50);

        $payload = [
            'periode' => $period->format('Y-m'),
            'ringkasan_angka' => $localReport,
            'pembanding_bulan_sebelumnya' => $previousReport ? [
                'periode' => $previousReport->period_month?->format('Y-m'),
                'ringkasan' => $previousReport->final_summary,
                'metrics' => $previousReport->metrics,
                'comparison' => $this->buildComparisonMetrics($this->buildMonthlyMetrics(Collection::make([])), $previousReport),
            ] : null,
            'transaksi' => $transactionsForPayload->map(fn ($trx) => [
                'tanggal' => $trx->date?->toDateString(),
                'tipe' => $trx->type?->name,
                'kategori' => $trx->category?->category_name,
                'dompet_asal' => $trx->sourceWallet?->name,
                'dompet_tujuan' => $trx->destinationWallet?->name,
                'nominal' => (float) $trx->amount,
                'catatan' => $trx->notes,
            ])->values()->all(),
            'truncated' => ($transactionsForPayload->count() < $transactionsCollection->count()) ? true : false,
            'transactions_count' => $transactionsCollection->count(),
        ];

        $prompt = implode("\n", [
            'Kamu adalah analis keuangan pribadi untuk aplikasi Bendaharaku.',
            'Buat laporan bulanan singkat dalam Bahasa Indonesia, ramah, padat, dan actionable.',
            'Format wajib:',
            '1. Ringkasan Bulan Ini',
            '2. Pemasukan vs Pengeluaran',
            '3. Kategori Pengeluaran Terbesar',
            '4. Perbandingan dengan Bulan Sebelumnya',
            '5. Insight Singkat',
            '6. Saran Praktis Bulan Ini',
            'Jika pembanding_bulan_sebelumnya null, tulis bahwa pembanding belum tersedia dan sarankan membuat laporan bulan sebelumnya.',
            'Jangan mengarang data di luar JSON. Jika data terbatas, bilang data masih sedikit.',
            'Jika daftar transaksi dipotong (truncated=true), beri tahu bahwa hasil mengabaikan transaksi sisanya.',
            'Data JSON (ringkas):',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $promptLength = strlen($prompt);
        $estimatedTokens = (int) max(1, round($promptLength / 4));
        Log::info('Gemini prompt prepared', [
            'user_id' => $user->id,
            'model' => $model,
            'transactions_sent' => $transactionsForPayload->count(),
            'transactions_total' => $transactionsCollection->count(),
            'truncated' => $payload['truncated'],
            'prompt_length_chars' => $promptLength,
            'estimated_tokens' => $estimatedTokens,
        ]);

        try {
            $text = $this->aiReportClient->sendPrompt($prompt, $credential->api_key, $model);

            if ($text === null) {
                return null;
            }

            Log::info('Gemini response received', [
                'user_id' => $user->id,
                'model' => $model,
                'text_snippet' => substr($text, 0, 300),
            ]);

            return ['summary' => $text, 'model' => $model];
        } catch (\Throwable $e) {
            Log::warning('Gemini monthly report exception', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
