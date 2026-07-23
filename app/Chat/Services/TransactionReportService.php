<?php

declare(strict_types=1);

namespace App\Chat\Services;

use App\Chat\Components\ReportSectionComponent;
use App\Chat\Components\TextComponent;
use App\Chat\DTOs\ChatResponse;
use App\Models\TransactionLog;
use App\Models\User;
use App\Support\MoneyFormatter;

class TransactionReportService
{
    public function buildTodayTransactionResponse(User $user, array $metadata): ChatResponse
    {
        $transactions = $user->transactionLogs()
            ->with(['type', 'category', 'sourceWallet', 'destinationWallet'])
            ->whereDate('date', now()->toDateString())
            ->latest('id')
            ->limit(10)
            ->get();

        if ($transactions->isEmpty()) {
            return ChatResponse::command([
                new TextComponent(translationKey: 'chat.command.transaction_today_empty'),
            ], $metadata);
        }

        $lines = [];
        foreach ($transactions as $transaction) {
            $lines[] = $this->formatTransactionLine($transaction);
        }

        $components = [
            new ReportSectionComponent(
                title: '',
                emoji: '📋',
                items: $lines,
                translationKey: 'chat.command.transaction_today_title',
            ),
        ];

        return ChatResponse::command($components, $metadata);
    }

    public function buildTypeSummaryResponse(User $user, string $type, array $metadata): ChatResponse
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $typeName = $type === 'income' ? 'Income' : 'Expense';

        $transactions = $user->transactionLogs()
            ->with(['category', 'sourceWallet', 'destinationWallet'])
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereHas('type', fn ($query) => $query->where('name', $typeName))
            ->latest('date')
            ->latest('id')
            ->get();

        $titleKey = $type === 'income' ? 'chat.command.income_title' : 'chat.command.expense_title';

        if ($transactions->isEmpty()) {
            return ChatResponse::command([
                new TextComponent(translationKey: $titleKey, bold: true),
                new TextComponent(translationKey: 'chat.command.month_type_empty'),
            ], $metadata);
        }

        $total = (float) $transactions->sum('amount');

        $items = [];
        foreach ($transactions->take(10) as $transaction) {
            $items[] = [
                'date' => $transaction->date?->format('d/m') ?? '-',
                'type' => strtolower($transaction->type?->name ?? 'transaksi'),
                'category' => $transaction->category?->category_name ?? '-',
                'category_icon' => $transaction->category?->icon ?? '📄',
                'amount' => MoneyFormatter::rupiah((float) $transaction->amount),
                'wallet' => $transaction->sourceWallet?->name ?? $transaction->destinationWallet?->name ?? '-',
            ];
        }

        $emoji = $type === 'income' ? '🟢' : '🔴';

        $components = [
            new ReportSectionComponent(
                title: '',
                emoji: $emoji,
                items: $items,
                translationKey: $titleKey,
                total: MoneyFormatter::rupiah($total),
                count: $transactions->count(),
            ),
        ];

        return ChatResponse::command($components, $metadata);
    }

    private function formatTransactionLine(TransactionLog $transaction): string
    {
        $type = $transaction->type?->name ?? 'Transaksi';
        $category = $transaction->category?->category_name ?? '-';
        $wallet = $transaction->sourceWallet?->name ?? $transaction->destinationWallet?->name ?? '-';
        $amount = MoneyFormatter::rupiah((float) $transaction->amount);
        $date = $transaction->date?->format('d/m') ?? '-';

        return "{$date} — {$type} — {$category} — {$amount} — {$wallet}";
    }
}
