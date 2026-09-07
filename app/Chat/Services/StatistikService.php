<?php

declare(strict_types=1);

namespace App\Chat\Services;

use App\Chat\Components\BarChartComponent;
use App\Chat\DTOs\ChatResponse;
use App\Models\User;
use Carbon\Carbon;

class StatistikService
{
    public function build(User $user, array $metadata): ChatResponse
    {
        $end = Carbon::today();
        $start = Carbon::today()->subDays(6);

        $logs = $user->transactionLogs()
            ->selectRaw('transaction_logs.date as date, SUM(CASE WHEN transaction_types.name = \'Income\' THEN transaction_logs.amount ELSE 0 END) as income, SUM(CASE WHEN transaction_types.name = \'Expense\' THEN transaction_logs.amount ELSE 0 END) as expense')
            ->join('transaction_types', 'transaction_types.id', '=', 'transaction_logs.type_id')
            ->whereBetween('transaction_logs.date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('transaction_logs.date')
            ->orderBy('transaction_logs.date')
            ->get()
            ->keyBy(fn ($r) => is_string($r->date) ? $r->date : $r->date->toDateString());

        $labels = [];
        $incomeData = [];
        $expenseData = [];
        $totalIncome = 0;
        $totalExpense = 0;

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            $row = $logs->get($key);
            $inc = (float) ($row->income ?? 0);
            $exp = (float) ($row->expense ?? 0);
            $labels[] = $d->format('d/m');
            $incomeData[] = $inc;
            $expenseData[] = $exp;
            $totalIncome += $inc;
            $totalExpense += $exp;
        }

        $components = [
            new BarChartComponent(
                title: '',
                labels: $labels,
                incomeData: $incomeData,
                expenseData: $expenseData,
                emoji: 'bar-chart-3',
                translationKey: 'chat.command.statistik_title',
                totalIncome: $totalIncome,
                totalExpense: $totalExpense,
            ),
        ];

        return ChatResponse::command($components, $metadata);
    }
}
