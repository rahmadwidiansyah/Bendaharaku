<?php

declare(strict_types=1);

namespace App\Chat\Services;

use App\Chat\Components\DividerComponent;
use App\Chat\Components\ReportSectionComponent;
use App\Chat\Components\TextComponent;
use App\Chat\DTOs\ChatResponse;
use App\Models\User;
use App\Models\Wallet;
use App\Support\MoneyFormatter;

class WalletReportService
{
    public function buildSaldoResponse(User $user, string $locale, array $metadata): ChatResponse
    {
        $wallets = Wallet::where('user_id', $user->id)
            ->whereIn('group_type', ['Asset', 'Liquid'])
            ->orderByDesc('balance')
            ->get();

        if ($wallets->isEmpty()) {
            return ChatResponse::command(
                components: [
                    new TextComponent(translationKey: 'chat.command.balance_empty'),
                ],
                metadata: $metadata,
            );
        }

        $totalBalance = 0.0;
        $items = [];

        foreach ($wallets as $w) {
            $totalBalance += (float) $w->balance;
            $items[] = [
                'name' => $w->name,
                'group_type' => $w->group_type,
                'icon' => $w->icon_url,
                'amount' => MoneyFormatter::rupiah((float) $w->balance),
            ];
        }

        $headerItems = [
            [
                'label' => trans('chat.command.balance_total_label', [], $locale),
                'value' => MoneyFormatter::rupiah($totalBalance),
            ],
            [
                'label' => trans('chat.command.balance_wallet_count', ['count' => $wallets->count()], $locale),
                'value' => '',
            ],
        ];

        $components = [
            new ReportSectionComponent(
                title: trans('chat.command.balance_title', [], $locale),
                emoji: '💳',
                items: $headerItems,
                translationKey: 'chat.command.balance_title',
                total: '',
                count: 0,
            ),
            new DividerComponent,
            new ReportSectionComponent(
                title: '',
                emoji: '',
                items: $items,
                translationKey: 'chat.command.balance_list',
                total: MoneyFormatter::rupiah($totalBalance),
                count: $wallets->count(),
            ),
        ];

        return ChatResponse::command(components: $components, metadata: $metadata);
    }

    public function buildWalletResponse(User $user, array $metadata): ChatResponse
    {
        $wallets = Wallet::where('user_id', $user->id)
            ->where('group_type', '!=', 'System')
            ->orderBy('group_type')
            ->orderByDesc('balance')
            ->get();

        if ($wallets->isEmpty()) {
            return ChatResponse::command([
                new TextComponent(translationKey: 'chat.command.balance_empty'),
            ], $metadata);
        }

        $lines = [];
        foreach ($wallets as $wallet) {
            $lines[] = "{$wallet->group_type} — {$wallet->name}: ".MoneyFormatter::rupiah((float) $wallet->balance);
        }

        $components = [
            new ReportSectionComponent(
                title: '',
                emoji: '👛',
                items: $lines,
                translationKey: 'chat.command.wallet_title',
            ),
        ];

        return ChatResponse::command($components, $metadata);
    }

    public function buildAssetResponse(User $user, array $metadata): ChatResponse
    {
        $assets = Wallet::where('user_id', $user->id)
            ->where('group_type', 'Asset')
            ->orderByDesc('balance')
            ->get();

        if ($assets->isEmpty()) {
            return ChatResponse::command([
                new TextComponent(translationKey: 'chat.command.asset_empty'),
            ], $metadata);
        }

        $total = (float) $assets->sum('balance');
        $lines = [];
        foreach ($assets as $asset) {
            $lines[] = "{$asset->name}: ".MoneyFormatter::rupiah((float) $asset->balance);
        }

        $components = [
            new ReportSectionComponent(
                title: '',
                emoji: '📈',
                items: $lines,
                translationKey: 'chat.command.asset_title',
            ),
            new DividerComponent,
            new TextComponent(
                translationKey: 'chat.command.balance_total',
                params: ['total' => MoneyFormatter::rupiah($total)],
                bold: true,
            ),
        ];

        return ChatResponse::command($components, $metadata);
    }
}
