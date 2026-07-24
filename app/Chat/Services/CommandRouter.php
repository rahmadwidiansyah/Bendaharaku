<?php

declare(strict_types=1);

namespace App\Chat\Services;

use App\Chat\ChatCommandRegistry;
use App\Chat\Components\DividerComponent;
use App\Chat\Components\SuggestionComponent;
use App\Chat\Components\TextComponent;
use App\Chat\DTOs\ChatContext;
use App\Chat\DTOs\ChatResponse;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CommandRouter
{
    public function __construct(
        private readonly WalletReportService $walletReport,
        private readonly CategoryReportService $categoryReport,
        private readonly TransactionReportService $transactionReport,
        private readonly MonthlyReportService $monthlyReport,
    ) {}

    public function route(
        string $text,
        User $user,
        ChatContext $context,
        float $startTime,
    ): ?ChatResponse {
        $lower = strtolower(trim($text));
        $traceId = $context->traceId;
        $startTimeReal = microtime(true);
        $latencyMs = (int) round((microtime(true) - $startTimeReal) * 1000);
        $normalizedText = $lower;
        $command = $this->normalizeCommand($lower);

        Log::debug('[PIPELINE:ROUTE] commandRouter invoked', [
            'trace_id'         => $traceId,
            'normalized_text'  => $normalizedText,
            'command'          => $command,
            'source'           => $context->sourcePrefix(),
            'platform'         => $context->platform->value,
        ]);

        $lane = $context->lane ?? 'default';
        $locale = $context->locale;

        $locale = $context->locale;
        $latency = (int) round((microtime(true) - $startTime) * 1000);
        $metadata = [
            'trace_id' => $context->traceId,
            'platform' => $context->platform->value,
            'latency_ms' => $latency,
        ];

        if ($command === '/saldo') {
            return $this->walletReport->buildSaldoResponse($user, $locale, $metadata);
        }

        if (in_array($command, ['/wallet', '/walet'], true)) {
            return $this->walletReport->buildWalletResponse($user, $metadata);
        }

        if ($command === '/kategori') {
            return $this->categoryReport->buildCategoryResponse($user, $metadata);
        }

        if ($command === '/aset') {
            return $this->walletReport->buildAssetResponse($user, $metadata);
        }

        if ($command === '/transaksi') {
            return $this->transactionReport->buildTodayTransactionResponse($user, $metadata);
        }

        if ($command === '/pemasukan') {
            return $this->transactionReport->buildTypeSummaryResponse($user, 'income', $metadata);
        }

        if ($command === '/pengeluaran') {
            return $this->transactionReport->buildTypeSummaryResponse($user, 'expense', $metadata);
        }

        if (in_array($command, ['/laporan', '/ringkasan'], true)) {
            return $this->monthlyReport->buildMonthlyReportResponse($user, $metadata, $text);
        }

        if ($command === '/web') {
            return $this->buildWebLinkResponse($locale, $metadata);
        }

        if (in_array($command ?? $lower, ['/help', '/start', 'hai', 'halo', 'hello', 'hi', 'ping', 'p', 'tes', 'test', 'help', 'tolong'])) {
            return $this->buildHelpResponse($user, $locale, $metadata);
        }

        if ($command !== null) {
            return ChatResponse::command(
                components: [
                    new TextComponent(
                        translationKey: 'chat.command.not_yet_implemented',
                        params: ['command' => $command],
                    ),
                ],
                metadata: $metadata,
            );
        }

        return null;
    }

    private function normalizeCommand(string $lower): ?string
    {
        $command = strtok($lower, " \t\n\r\0\x0B") ?: $lower;

        return match ($command) {
            '/saldo', 'saldo' => '/saldo',
            '/wallet', 'wallet', '/walet', 'walet', 'dompet', '/dompet' => '/wallet',
            '/kategori', 'kategori' => '/kategori',
            '/aset', 'aset' => '/aset',
            '/transaksi', 'transaksi' => '/transaksi',
            '/pemasukan', 'pemasukan' => '/pemasukan',
            '/pengeluaran', 'pengeluaran' => '/pengeluaran',
            '/laporan', 'laporan' => '/laporan',
            '/ringkasan', 'ringkasan' => '/ringkasan',
            '/help', 'help', '/start' => $command,
            '/web', 'web' => '/web',
            default => str_starts_with($command, '/') ? $command : null,
        };
    }

    private function buildHelpResponse(User $user, string $locale, array $metadata): ChatResponse
    {
        $platform = $metadata['platform'] ?? 'web';
        $registry = new ChatCommandRegistry;
        $commands = $registry->forPlatform($platform, includeHidden: false);

        $components = [
            new TextComponent(
                translationKey: 'chat.command.help_greeting',
                params: ['name' => $user->name],
                bold: true,
            ),
            new TextComponent(translationKey: 'chat.command.help_intro'),
            new DividerComponent,

            new TextComponent(translationKey: 'chat.command.help_guide', bold: true),
            new TextComponent(translationKey: 'chat.command.help_example_intro'),
            new DividerComponent,

            new SuggestionComponent(
                messageKey: 'chat.command.help_example_expense',
                params: [],
                actionUrl: null,
            ),
            new SuggestionComponent(
                messageKey: 'chat.command.help_example_income',
                params: [],
                actionUrl: null,
            ),
            new SuggestionComponent(
                messageKey: 'chat.command.help_example_transfer',
                params: [],
                actionUrl: null,
            ),
            new SuggestionComponent(
                messageKey: 'chat.command.help_example_debt',
                params: [],
                actionUrl: null,
            ),
            new DividerComponent,

            new TextComponent(translationKey: 'chat.command.help_commands_title', bold: true),
        ];

        foreach ($commands as $cmd) {
            $components[] = new TextComponent(
                translationKey: 'chat.command.help_cmd_template',
                params: [
                    'icon' => $cmd['icon'],
                    'command' => $cmd['command'],
                    'description' => trans($cmd['description'], [], $locale),
                ],
            );
        }

        return ChatResponse::command(
            components: $components,
            metadata: $metadata,
        );
    }

    private function buildWebLinkResponse(string $locale, array $metadata): ChatResponse
    {
        $appUrl = config('app.url', 'https://bendaharaku.widihhh.my.id');

        return ChatResponse::command([
            new TextComponent(
                translationKey: 'chat.command.web_link_msg',
                params: ['url' => $appUrl],
            ),
        ], $metadata);
    }
}
