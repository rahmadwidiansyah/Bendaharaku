<?php

declare(strict_types=1);

namespace App\Chat;

use App\Chat\DTOs\ChatRequest;
use App\Chat\DTOs\ChatResponse;
use App\Chat\DTOs\ChatContext;
use App\Chat\Components\TextComponent;
use App\Chat\Components\DividerComponent;
use App\Chat\Components\ReportSectionComponent;
use App\Chat\Components\TransactionCardComponent;
use App\Chat\Components\SummaryCardComponent;
use App\Chat\Components\ErrorComponent;
use App\Chat\Components\WarningComponent;
use App\Chat\Components\SuggestionComponent;
use App\Chat\Errors\ErrorDetail;
use App\Enums\AiProvider;
use App\Enums\ChatErrorSeverity;
use App\Enums\ChatIntent;
use App\Models\MonthlyReport;
use App\Models\UserAiCredential;
use App\Models\Wallet;
use App\Services\Chat\ChatTransactionOrchestrator;
use App\DTO\MultiTransactionResult;
use App\DTO\MultiTransactionItem;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiProviderException;
use App\Exceptions\CategoryNotFoundException;
use App\Exceptions\WalletNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Throwable;
use InvalidArgumentException;

/**
 * Entry point tunggal untuk semua platform chat.
 *
 * Tanggung jawab:
 * 1. Menerima ChatRequest dari Adapter (Telegram, Web, WhatsApp, dll)
 * 2. Mendelegasikan ke ChatTransactionOrchestrator (tidak diubah)
 * 3. Mengkonversi output Orchestrator → ChatResponse terstruktur
 * 4. Menggunakan ErrorDetail bukan string mentah
 *
 * Yang TIDAK dilakukan service ini:
 * - Tidak tahu tentang Telegram, WhatsApp, Web
 * - Tidak menulis Markdown atau HTML
 * - Tidak memanggil API platform
 * - Tidak meng-hardcode teks
 *
 * Orchestrator tetap tidak diubah (Strangler Fig Pattern).
 * Setelah seluruh platform migrasi ke service ini, Orchestrator
 * bisa direfactor secara terpisah tanpa risiko.
 */
class ChatApplicationService
{
    public function __construct(
        private readonly ChatTransactionOrchestrator $orchestrator,
    ) {}

    /**
     * Proses satu pesan dari user dan kembalikan ChatResponse.
     *
     * Dipanggil oleh Adapter. Tidak ada kode platform di sini.
     */
    public function handleMessage(ChatRequest $request): ChatResponse
    {
        $context   = $request->context;
        $user      = $request->user;
        $text      = $request->normalizedMessage();
        $source    = $context->sourcePrefix();
        $startTime = microtime(true);

        Log::info('ChatApplicationService: processing message', [
            'trace_id' => $context->traceId,
            'platform' => $context->platform->value,
            'user_id'  => $user->id,
            'length'   => strlen($text),
        ]);

        // ── Command handling (Web platform) ───────────────────────
        // TelegramAdapter menangani command sebelum memanggil service ini.
        // Web platform tidak punya layer tersebut, jadi kita handle di sini.
        // Command seperti /help, /saldo, dll tidak perlu melewati AI orchestrator.
        // Guard: HANYA jalankan untuk Web — Telegram sudah handle sendiri.
        if ($context->platform === \App\Enums\ChatPlatform::Web) {
            $commandResponse = $this->handleWebCommand($text, $user, $context, $startTime);
            if ($commandResponse !== null) {
                return $commandResponse;
            }
        }

        try {
            $result = $this->orchestrator->process($user, $text, $source);

            $latency  = (int) round((microtime(true) - $startTime) * 1000);
            $metadata = $this->buildMetadata($result, $context, $latency);

            // ── Multi-transaction ──────────────────────────────────
            if (!empty($result['is_multi'])) {
                return $this->convertMultiResult($result['multi_result'], $context, $metadata);
            }

            // ── Single gagal (AI error, validasi, dll) ─────────────
            if (!$result['success']) {
                return $this->convertSingleFailure($result, $metadata);
            }

            // ── Single sukses ──────────────────────────────────────
            return $this->convertSingleSuccess($result, $context, $metadata, $text);

        } catch (AiConfigurationException $e) {
            return $this->failureResponse([ErrorDetail::aiNotConfigured()], $context, $startTime);

        } catch (AiRateLimitException $e) {
            return $this->failureResponse([ErrorDetail::aiRateLimit($e->getMessage())], $context, $startTime);

        } catch (AiTimeoutException $e) {
            return $this->failureResponse([ErrorDetail::aiTimeout($e->getMessage())], $context, $startTime);

        } catch (AiProviderException $e) {
            return $this->failureResponse([ErrorDetail::aiProviderError($e->getMessage(), $e->getMessage())], $context, $startTime);

        } catch (CategoryNotFoundException | WalletNotFoundException $e) {
            // Dilempar oleh single flow — multi sudah menangkap per-item
            $error = str_contains($e->getMessage(), 'ategori')
                ? ErrorDetail::categoryNotFound($e->getMessage())
                : ErrorDetail::walletNotFound($e->getMessage());
            return $this->failureResponse([$error], $context, $startTime);

        } catch (ModelNotFoundException $e) {
            return $this->failureResponse([
                new ErrorDetail(
                    code:       'DATA_NOT_FOUND',
                    messageKey: 'chat.error.data_not_found_single',
                    severity:   ChatErrorSeverity::Error,
                ),
            ], $context, $startTime);

        } catch (InvalidArgumentException | \RuntimeException $e) {
            return $this->failureResponse([
                new ErrorDetail(
                    code:       'VALIDATION_ERROR',
                    messageKey: 'chat.error.runtime',
                    params:     ['message' => $e->getMessage()],
                    severity:   ChatErrorSeverity::Error,
                ),
            ], $context, $startTime);

        } catch (Throwable $e) {
            Log::error('ChatApplicationService: unhandled exception', [
                'trace_id'  => $context->traceId,
                'user_id'   => $user->id,
                'exception' => $e,
            ]);
            return $this->failureResponse([ErrorDetail::systemError()], $context, $startTime);
        }
    }

    // ── Converters: Orchestrator array → ChatResponse ────────────

    /**
     * Single transaction sukses (termasuk draft).
     */
    private function convertSingleSuccess(
        array       $result,
        ChatContext $context,
        array       $metadata,
        string      $originalText,
    ): ChatResponse {
        $trx        = $result['transaction'];
        $isCleared  = $trx->is_cleared;
        $locale     = $context->locale;

        $components = [];

        // Kartu transaksi detail
        $components[] = new TransactionCardComponent(
            transaction: $trx,
            showDetails: true,
        );

        if (!$isCleared && $trx->sourceWallet?->group_type === 'System') {
            $components[] = new WarningComponent(
                messageKey: 'chat.wallet.missing_choose',
            );
        }

        // Divider + footer AI
        $components[] = new DividerComponent();
        $components[] = new TextComponent(
            translationKey: 'chat.transaction.label_original_msg',
        );
        $components[] = new TextComponent(
            translationKey: 'chat.transaction.label_ai_provider',
            params: [
                'provider'    => $metadata['provider'] ?? '',
                'confidence'  => isset($metadata['confidence'])
                    ? round($metadata['confidence'] * 100) . '%'
                    : '-',
            ],
        );

        if ($isCleared) {
            return ChatResponse::singleSuccess($components, $metadata);
        }

        return ChatResponse::draft($components, $metadata);
    }

    /**
     * Single transaction gagal — Orchestrator return ['success'=>false, 'message'=>'...'].
     * Pada Tahap 1 ini, message dari Orchestrator masih bisa mengandung Telegram Markdown.
     * Kita bungkus apa adanya dengan ErrorDetail generic.
     * Tahap 2 nanti: Orchestrator return ErrorDetail langsung.
     */
    private function convertSingleFailure(array $result, array $metadata): ChatResponse
    {
        // Coba kenali tipe error dari message string (temporary, Tahap 1)
        $message = $result['message'] ?? '';
        $error   = $this->detectErrorFromMessage($message);

        return ChatResponse::failure([$error], [], $metadata);
    }

    /**
     * Multi-transaction: konversi MultiTransactionResult → ChatResponse.
     */
    private function convertMultiResult(
        MultiTransactionResult $multiResult,
        ChatContext            $context,
        array                  $metadata,
    ): ChatResponse {
        $components = [];
        $errors     = [];

        // Header: SummaryCard
        $components[] = new SummaryCardComponent(
            total:      $multiResult->totalCount(),
            success:    $multiResult->successCount(),
            failed:     $multiResult->failedCount(),
            confidence: $multiResult->confidence,
        );

        $components[] = new DividerComponent();

        // Setiap item, urutan dipertahankan
        foreach ($multiResult->results as $item) {
            /** @var MultiTransactionItem $item */
            if ($item->isSuccess()) {
                $components[] = new TransactionCardComponent(
                    transaction: $item->transaction,
                    index:       $item->index,
                    showDetails: false,
                );
            } else {
                // Error per-item sebagai ErrorComponent (inline dalam list)
                $components[] = new ErrorComponent(
                    messageKey: $this->mapErrorCodeToKey($item->errorCode?->value),
                    params:     $this->extractErrorParams($item->reason ?? '', $item->errorCode?->value),
                    raw:        $item->raw,
                    index:      $item->index,
                    severity:   ChatErrorSeverity::Error,
                    recoverable: true,
                );
            }
        }

        // Footer AI
        $components[] = new DividerComponent();
        $components[] = new TextComponent(
            translationKey: 'chat.transaction.label_ai_provider',
            params: [
                'provider'   => $metadata['provider'] ?? '',
                'confidence' => isset($metadata['confidence'])
                    ? round($metadata['confidence'] * 100) . '%'
                    : '-',
            ],
        );

        return ChatResponse::multiResult(
            hasAnySuccess: $multiResult->hasAnySuccess(),
            components:    $components,
            metadata:      $metadata,
        );
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function failureResponse(
        array       $errors,
        ChatContext $context,
        float       $startTime,
    ): ChatResponse {
        $latency = (int) round((microtime(true) - $startTime) * 1000);
        return ChatResponse::failure($errors, [], [
            'trace_id'   => $context->traceId,
            'platform'   => $context->platform->value,
            'latency_ms' => $latency,
        ]);
    }

    private function buildMetadata(array $result, ChatContext $context, int|float $latency): array
    {
        $usage        = $result['usage'] ?? ($result['multi_result']?->usage ?? []);
        $totalTokens  = $usage['total'] ?? null;

        return array_filter([
            'trace_id'     => $context->traceId,
            'platform'     => $context->platform->value,
            'provider'     => $result['provider'] ?? ($result['multi_result']?->provider ?? null),
            'model'        => $result['model'] ?? ($result['multi_result']?->model ?? null),
            'confidence'   => $result['confidence'] ?? ($result['multi_result']?->confidence ?? null),
            'latency_ms'   => (int) $latency,
            'total_tokens' => $totalTokens,
        ]);
    }

    /**
     * Deteksi tipe error dari message string Orchestrator (Tahap 1 bridge).
     * Akan dihapus di Tahap 2 ketika Orchestrator return ErrorDetail langsung.
     */
    private function detectErrorFromMessage(string $message): ErrorDetail
    {
        if (str_contains($message, 'Wallet') || str_contains($message, 'Dompet')) {
            return new ErrorDetail('WALLET_NOT_FOUND', 'chat.wallet.not_found', ['name' => '?']);
        }
        if (str_contains($message, 'Kategori') || str_contains($message, 'Category')) {
            return new ErrorDetail('CATEGORY_NOT_FOUND', 'chat.category.not_found', ['name' => '?']);
        }
        if (str_contains($message, 'Nominal') || str_contains($message, 'amount')) {
            return new ErrorDetail('INVALID_AMOUNT', 'chat.validation.missing_amount');
        }
        if (str_contains($message, 'kategori') || str_contains($message, 'category')) {
            return new ErrorDetail('CATEGORY_NOT_FOUND', 'chat.validation.missing_category');
        }
        if (str_contains($message, 'Hutang') || str_contains($message, 'hashtag') || str_contains($message, '#')) {
            return new ErrorDetail('MISSING_SUBJECT', 'chat.validation.missing_debt_subject');
        }
        if (str_contains($message, 'draft') || str_contains($message, 'Draft')) {
            return new ErrorDetail('DRAFT_SAVED', 'chat.transaction.draft_saved', severity: ChatErrorSeverity::Warning);
        }
        if (str_contains($message, 'AI Gagal') || str_contains($message, 'AI Failed')) {
            return new ErrorDetail(
                'AI_PARSE_FAILED',
                'chat.ai.parse_failed',
                ['reason' => trans('chat.ai.parse_failed_default')],
            );
        }
        // Fallback: gunakan pesan asli apa adanya (Tahap 1 bridge, akan dihapus Tahap 2)
        return new ErrorDetail(
            code:       'UNKNOWN',
            messageKey: 'chat.error.system',
            severity:   ChatErrorSeverity::Error,
        );
    }

    /**
     * Map MultiTransactionErrorCode value ke translation key.
     */
    private function mapErrorCodeToKey(?string $errorCode): string
    {
        return match ($errorCode) {
            'WALLET_NOT_FOUND'     => 'chat.wallet.not_found',
            'CATEGORY_NOT_FOUND'   => 'chat.category.not_found',
            'INVALID_AMOUNT'       => 'chat.validation.invalid_amount',
            'SAME_WALLET'          => 'chat.validation.same_wallet',
            'INSUFFICIENT_BALANCE' => 'chat.wallet.insufficient',
            'VALIDATION_ERROR'     => 'chat.validation.invalid_amount',
            default                => 'chat.error.system',
        };
    }

    /**
     * Ekstrak parameter untuk translation dari reason string dan error code.
     * Contoh: "Dompet 'spay' tidak ditemukan." → ['name' => 'spay']
     */
    private function extractErrorParams(string $reason, ?string $errorCode): array
    {
        if (in_array($errorCode, ['WALLET_NOT_FOUND', 'CATEGORY_NOT_FOUND'])) {
            // Coba ekstrak nama dalam tanda kutip: 'spay', "spay"
            if (preg_match("/['\"]([^'\"]+)['\"]/", $reason, $m)) {
                return ['name' => $m[1]];
            }
        }
        return ['message' => $reason];
    }

    // ── Web Command Handler ────────────────────────────────────────

    /**
     * Handle command chat (diawali '/') yang tidak perlu melewati AI.
     *
     * Dipanggil sebelum orchestrator. Jika bukan command, return null agar
     * alur normal tetap berjalan.
     *
     * Platform Web tidak punya layer handleCommand sendiri (berbeda dengan
     * TelegramAdapter), sehingga command perlu ditangkap di sini.
     */
    private function handleWebCommand(
        string      $text,
        \App\Models\User $user,
        ChatContext $context,
        float       $startTime,
    ): ?ChatResponse {
        $lower = strtolower(trim($text));
        $command = $this->normalizeWebCommand($lower);

        // Bukan command → lanjut ke orchestrator
        if ($command === null && !in_array($lower, ['hai', 'halo', 'hello', 'hi', 'ping', 'p', 'tes', 'test', 'help', 'tolong'])) {
            return null;
        }

        $locale   = $context->locale;
        $latency  = (int) round((microtime(true) - $startTime) * 1000);
        $metadata = [
            'trace_id'   => $context->traceId,
            'platform'   => $context->platform->value,
            'latency_ms' => $latency,
        ];

        // /saldo — laporan saldo dompet
        if ($command === '/saldo') {
            return $this->buildSaldoResponse($user, $locale, $metadata);
        }

        if (in_array($command, ['/wallet', '/walet'], true)) {
            return $this->buildWalletResponse($user, $metadata);
        }

        if ($command === '/kategori') {
            return $this->buildCategoryResponse($user, $metadata);
        }

        if ($command === '/aset') {
            return $this->buildAssetResponse($user, $metadata);
        }

        if ($command === '/transaksi') {
            return $this->buildTodayTransactionResponse($user, $metadata);
        }

        if ($command === '/pemasukan') {
            return $this->buildTypeSummaryResponse($user, 'income', $metadata);
        }

        if ($command === '/pengeluaran') {
            return $this->buildTypeSummaryResponse($user, 'expense', $metadata);
        }

        if (in_array($command, ['/laporan', '/ringkasan'], true)) {
            return $this->buildMonthlyReportResponse($user, $metadata, $text);
        }

        // /help, /start, greeting, ping
        if (in_array($command ?? $lower, ['/help', '/start', 'hai', 'halo', 'hello', 'hi', 'ping', 'p', 'tes', 'test', 'help', 'tolong'])) {
            return $this->buildHelpResponse($user, $locale, $metadata);
        }

        // Command lain yang terdaftar di registry tapi belum diimplementasi
        // Tampilkan pesan "fitur dalam pengembangan" daripada system error
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

    private function normalizeWebCommand(string $lower): ?string
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
            default => str_starts_with($command, '/') ? $command : null,
        };
    }

    private function buildSaldoResponse(\App\Models\User $user, string $locale, array $metadata): ChatResponse
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

        // Buat baris untuk setiap dompet
        $totalBalance = 0.0;
        $lines        = [];

        foreach ($wallets as $w) {
            $totalBalance += (float) $w->balance;
            $lines[] = \App\Support\MoneyFormatter::rupiah((float) $w->balance) . ' — ' . $w->name;
        }

        // Gunakan ReportSectionComponent agar frontend dapat merender list dengan styling konsisten
        $components = [
            new \App\Chat\Components\ReportSectionComponent(
                title: '',
                emoji: '💳',
                items: $lines,
                translationKey: 'chat.command.balance_title',
            ),
            new DividerComponent(),
            new TextComponent(
                translationKey: 'chat.command.balance_total',
                params: ['total' => \App\Support\MoneyFormatter::rupiah($totalBalance)],
                bold: true,
            ),
        ];

        return ChatResponse::command(components: $components, metadata: $metadata);
    }

    private function buildWalletResponse(\App\Models\User $user, array $metadata): ChatResponse
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
            $lines[] = "{$wallet->group_type} — {$wallet->name}: " . \App\Support\MoneyFormatter::rupiah((float) $wallet->balance);
        }

        $components = [
            new \App\Chat\Components\ReportSectionComponent(
                title: '',
                emoji: '👛',
                items: $lines,
                translationKey: 'chat.command.wallet_title',
            ),
        ];

        return ChatResponse::command($components, $metadata);
    }

    private function buildAssetResponse(\App\Models\User $user, array $metadata): ChatResponse
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
            $lines[] = "{$asset->name}: " . \App\Support\MoneyFormatter::rupiah((float) $asset->balance);
        }

        $components = [
            new \App\Chat\Components\ReportSectionComponent(
                title: '',
                emoji: '📈',
                items: $lines,
                translationKey: 'chat.command.asset_title',
            ),
            new DividerComponent(),
            new TextComponent(
                translationKey: 'chat.command.balance_total',
                params: ['total' => \App\Support\MoneyFormatter::rupiah($total)],
                bold: true,
            ),
        ];

        return ChatResponse::command($components, $metadata);
    }

    private function buildCategoryResponse(\App\Models\User $user, array $metadata): ChatResponse
    {
        $categories = $user->categories()
            ->with('type')
            ->orderBy('type_id')
            ->orderBy('category_name')
            ->get();

        if ($categories->isEmpty()) {
            return ChatResponse::command([
                new TextComponent(translationKey: 'chat.command.category_empty'),
            ], $metadata);
        }

        $lines = [];
        foreach ($categories->groupBy(fn($category) => $category->type?->name ?? 'Other') as $typeName => $items) {
            $lines[] = "{$typeName}: " . $items->pluck('category_name')->join(', ');
        }

        $components = [
            new \App\Chat\Components\ReportSectionComponent(
                title: '',
                emoji: '🏷️',
                items: $lines,
                translationKey: 'chat.command.category_title',
            ),
        ];

        return ChatResponse::command($components, $metadata);
    }

    private function buildTodayTransactionResponse(\App\Models\User $user, array $metadata): ChatResponse
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
            new \App\Chat\Components\ReportSectionComponent(
                title: '',
                emoji: '📋',
                items: $lines,
                translationKey: 'chat.command.transaction_today_title',
            ),
        ];

        return ChatResponse::command($components, $metadata);
    }

    private function buildTypeSummaryResponse(\App\Models\User $user, string $type, array $metadata): ChatResponse
    {
        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();
        $typeName    = $type === 'income' ? 'Income' : 'Expense';

        $transactions = $user->transactionLogs()
            ->with(['category', 'sourceWallet', 'destinationWallet'])
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereHas('type', fn($query) => $query->where('name', $typeName))
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

        $lines = [];
        foreach ($transactions->take(10) as $transaction) {
            $lines[] = $this->formatTransactionLine($transaction);
        }

        $emoji = $type === 'income' ? '🟢' : '🔴';

        $components = [
            new \App\Chat\Components\ReportSectionComponent(
                title: '',
                emoji: $emoji,
                items: $lines,
                translationKey: $titleKey,
            ),
            new TextComponent(
                translationKey: 'chat.command.month_type_total',
                params: [
                    'count' => $transactions->count(),
                    'total' => \App\Support\MoneyFormatter::rupiah($total),
                ],
                bold: true,
            ),
        ];

        return ChatResponse::command($components, $metadata);
    }

    private function buildMonthlyReportResponse(\App\Models\User $user, array $metadata, string $rawText): ChatResponse
    {
        $period = $this->resolveReportPeriod($rawText);
        $monthStart = $period->copy()->startOfMonth();
        $monthEnd   = $period->copy()->endOfMonth();
        $periodKey = $monthStart->toDateString();
        
        // Auto-generate previous month report jika belum ada
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
                'title' => 'Laporan ' . $monthStart->translatedFormat('F Y'),
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

        // Build styled sections
        $components = [
            new ReportSectionComponent(
                title: $monthStart->translatedFormat('F Y'),
                emoji: '📊',
                translationKey: 'chat.command.report_title_period',
            ),
        ];

        // Main summary section
        if ($finalReport) {
            $components[] = new TextComponent(
                translationKey: 'chat.command.report_summary',
                params: ['summary' => $finalReport],
            );
        }

        // Comparison section jika ada data bulan sebelumnya
        if ($comparisonMetrics && !empty($comparisonMetrics)) {
            $components[] = new DividerComponent();
            $comparisonItems = [];

            if (isset($comparisonMetrics['income_diff'])) {
                $trend = $comparisonMetrics['income_trend'] ?? 'stable';
                $emoji = match ($trend) {
                    'up' => '📈',
                    'down' => '📉',
                    default => '➡️',
                };
                $comparisonItems[] = sprintf(
                    "%s Pendapatan: %s (vs bulan lalu)",
                    $emoji,
                    $this->formatCurrency($comparisonMetrics['income_diff'])
                );
            }

            if (isset($comparisonMetrics['expense_diff'])) {
                $trend = $comparisonMetrics['expense_trend'] ?? 'stable';
                $emoji = match ($trend) {
                    'up' => '📈',
                    'down' => '📉',
                    default => '➡️',
                };
                $comparisonItems[] = sprintf(
                    "%s Pengeluaran: %s (vs bulan lalu)",
                    $emoji,
                    $this->formatCurrency($comparisonMetrics['expense_diff'])
                );
            }

            if (!empty($comparisonItems)) {
                $components[] = new ReportSectionComponent(
                    title: 'Perbandingan dengan Bulan Lalu',
                    emoji: '📊',
                    items: $comparisonItems,
                    translationKey: 'chat.command.report_comparison',
                );
            }
        }

        // Save notification
        $components[] = new DividerComponent();
        $components[] = new TextComponent(translationKey: 'chat.command.report_saved');

        if ($geminiReport === null) {
            $components[] = new WarningComponent(
                messageKey: 'chat.command.report_gemini_unavailable',
            );
        }

        return ChatResponse::command($components, $metadata);
    }

    private function formatCurrency(float $amount): string
    {
        $formatted = number_format(abs($amount), 0, ',', '.');
        return ($amount < 0 ? '-' : '+') . 'Rp ' . $formatted;
    }

    private function resolveReportPeriod(string $rawText): Carbon
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
            if (!preg_match('/\b' . preg_quote($name, '/') . '\b/u', $text)) {
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

    private function buildMonthlyMetrics($transactions)
    {
        // Normalize input: accept Collection or array — treat consistently
        $transactions = \Illuminate\Support\Collection::make($transactions);

        $income = (float) $transactions
            ->filter(fn($trx) => strtolower($trx->type?->name ?? '') === 'income')
            ->sum('amount');
        $expense = (float) $transactions
            ->filter(fn($trx) => strtolower($trx->type?->name ?? '') === 'expense')
            ->sum('amount');

        return \Illuminate\Support\Collection::make([
            'transaction_count' => $transactions->count(),
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
            'top_expense_categories' => $transactions
                ->filter(fn($trx) => strtolower($trx->type?->name ?? '') === 'expense')
                ->groupBy(fn($trx) => $trx->category?->category_name ?? '-')
                ->map(fn($items) => (float) $items->sum('amount'))
                ->sortDesc()
                ->take(5)
                ->toArray(),
        ]);
    }

    private function buildLocalMonthlyReport($transactions, ?Carbon $period = null, ?MonthlyReport $previousReport = null)
    {
        // Normalize input to Collection for consistent operations
        $transactions = \Illuminate\Support\Collection::make($transactions);

        $income = (float) $transactions
            ->filter(fn($trx) => strtolower($trx->type?->name ?? '') === 'income')
            ->sum('amount');
        $expense = (float) $transactions
            ->filter(fn($trx) => strtolower($trx->type?->name ?? '') === 'expense')
            ->sum('amount');
        $net = $income - $expense;

        $topCategories = $transactions
            ->filter(fn($trx) => strtolower($trx->type?->name ?? '') === 'expense')
            ->groupBy(fn($trx) => $trx->category?->category_name ?? '-')
            ->map(fn($items) => (float) $items->sum('amount'))
            ->sortDesc()
            ->take(5)
            ->map(fn($amount, $category) => "{$category}: " . \App\Support\MoneyFormatter::rupiah($amount))
            ->values()
            ->join("\n");

        $reportText = implode("\n", array_filter([
            'Periode: ' . ($period ? $period->translatedFormat('F Y') : now()->translatedFormat('F Y')),
            'Pemasukan: ' . \App\Support\MoneyFormatter::rupiah($income),
            'Pengeluaran: ' . \App\Support\MoneyFormatter::rupiah($expense),
            'Selisih: ' . \App\Support\MoneyFormatter::rupiah($net),
            $previousReport ? "Pembanding bulan sebelumnya:\n" . $previousReport->final_summary : null,
            $topCategories ? "Top kategori pengeluaran:\n{$topCategories}" : null,
        ]));

        // If called without a period (unit tests), return structured array for easier assertions
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

    private function generateGeminiMonthlyReport(
        \App\Models\User $user,
        $transactions,
        string $localReport,
        Carbon $period,
        ?MonthlyReport $previousReport = null,
    ): ?array
    {
        $credential = UserAiCredential::where('user_id', $user->id)
            ->where('provider', AiProvider::Gemini->value)
            ->where('is_valid', true)
            ->first();

        if (!$credential || blank($credential->api_key)) {
            return null;
        }

        $preference = $user->aiPreferences()
            ->where('provider', AiProvider::Gemini->value)
            ->first();
        $model = $preference?->selected_model ?: AiProvider::Gemini->defaultModel();

        // Limit transactions to avoid excessively long prompts (keep top 50)
        // Ensure transactions is a Collection to avoid "call to member function filter() on array" errors
        $transactionsCollection = \Illuminate\Support\Collection::make($transactions);
        $transactionsForPayload = $transactionsCollection->take(50);

        $payload = [
            'periode' => $period->format('Y-m'),
            'ringkasan_angka' => $localReport,
            'pembanding_bulan_sebelumnya' => $previousReport ? [
                'periode' => $previousReport->period_month?->format('Y-m'),
                'ringkasan' => $previousReport->final_summary,
                'metrics' => $previousReport->metrics,
                'comparison' => $this->buildComparisonMetrics($this->buildMonthlyMetrics(\Illuminate\Support\Collection::make([])), $previousReport),
            ] : null,
            // Use a truncated transactions list to keep prompt size bounded
            'transaksi' => $transactionsForPayload->map(fn($trx) => [
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

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

            // Observability: prompt size, transactions count, estimated tokens
            $promptLength = strlen($prompt);
            $estimatedTokens = (int) max(1, round($promptLength / 4)); // rough heuristic
            Log::info('Gemini prompt prepared', [
                'user_id' => $user->id,
                'model' => $model,
                'transactions_sent' => $transactionsForPayload->count(),
                'transactions_total' => $transactionsCollection->count(),
                'truncated' => $payload['truncated'],
                'prompt_length_chars' => $promptLength,
                'estimated_tokens' => $estimatedTokens,
            ]);

            // Manual retry loop so we can log attempt counts and timings
            $maxAttempts = 3;
            $attempt = 0;
            $response = null;
            $lastException = null;

            while ($attempt < $maxAttempts) {
                $attempt++;
                $start = microtime(true);
                try {
                    $response = Http::timeout(60)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post($url . '?key=' . $credential->api_key, [
                            'contents' => [['parts' => [['text' => $prompt]]]],
                        ]);

                    $elapsedMs = (int) round((microtime(true) - $start) * 1000);
                    Log::info('Gemini request attempt', [
                        'user_id' => $user->id,
                        'model' => $model,
                        'attempt' => $attempt,
                        'elapsed_ms' => $elapsedMs,
                        'status' => $response->status(),
                    ]);

                    // If successful, break
                    if ($response->successful()) break;

                    // Non-successful responses will be logged by assertSuccessful below

                } catch (Throwable $e) {
                    $elapsedMs = (int) round((microtime(true) - $start) * 1000);
                    Log::warning('Gemini request exception', [
                        'user_id' => $user->id,
                        'model' => $model,
                        'attempt' => $attempt,
                        'elapsed_ms' => $elapsedMs,
                        'exception' => $e->getMessage(),
                    ]);
                    $lastException = $e;
                }

                // Backoff between attempts
                if ($attempt < $maxAttempts) {
                    sleep(1);
                }
            }

            if ($response === null) {
                Log::warning('Gemini all attempts failed', ['user_id' => $user->id, 'model' => $model]);
                return null;
            }

            $this->assertSuccessful($response, 'Gemini');

            $text = trim((string) $response->json('candidates.0.content.parts.0.text'));
            Log::info('Gemini response received', [
                'user_id' => $user->id,
                'model' => $model,
                'text_snippet' => substr($text, 0, 300),
            ]);

            return $text !== '' ? ['summary' => $text, 'model' => $model] : null;
        } catch (Throwable $e) {
            Log::warning('Gemini monthly report exception', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
            return null;
        }

    }

    private function formatTransactionLine(\App\Models\TransactionLog $transaction): string
    {
        $type = $transaction->type?->name ?? 'Transaksi';
        $category = $transaction->category?->category_name ?? '-';
        $wallet = $transaction->sourceWallet?->name ?? $transaction->destinationWallet?->name ?? '-';
        $amount = \App\Support\MoneyFormatter::rupiah((float) $transaction->amount);
        $date = $transaction->date?->format('d/m') ?? '-';

        return "{$date} — {$type} — {$category} — {$amount} — {$wallet}";
    }

    private function assertSuccessful(\Illuminate\Http\Client\Response $response, string $provider): void
    {
        if ($response->successful()) return;
        $statusCode = $response->status();
        if ($statusCode === 429)                        throw new AiRateLimitException($provider);
        if (in_array($statusCode, [408, 503, 504]))    throw new AiTimeoutException($provider);
        if (in_array($statusCode, [401, 403]))         throw new AiProviderException($provider, "API Key tidak valid (HTTP {$statusCode}).");
        throw new AiProviderException($provider, "HTTP {$statusCode}: " . substr($response->body(), 0, 200));
    }

    private function buildHelpResponse(\App\Models\User $user, string $locale, array $metadata): ChatResponse
    {
        return ChatResponse::command(
            components: [
                new TextComponent(
                    translationKey: 'chat.command.help_greeting',
                    params: ['name' => $user->name],
                    bold: true,
                ),
                new TextComponent(translationKey: 'chat.command.help_intro'),
                new DividerComponent(),
                new TextComponent(translationKey: 'chat.command.help_guide'),
                new \App\Chat\Components\SuggestionComponent(
                    messageKey: 'chat.command.help_example_expense',
                ),
                new \App\Chat\Components\SuggestionComponent(
                    messageKey: 'chat.command.help_example_income',
                ),
                new \App\Chat\Components\SuggestionComponent(
                    messageKey: 'chat.command.help_example_transfer',
                ),
            ],
            metadata: $metadata,
        );
    }

    private function buildComparisonMetrics(array|\Illuminate\Support\Collection $currentMetrics, ?MonthlyReport $previousReport = null): ?array
    {
        if (!$previousReport || !$previousReport->metrics) {
            return null;
        }

        $prev = $previousReport->metrics;
        // Normalize current metrics: accept array or Collection
        $curr = is_array($currentMetrics) ? $currentMetrics : \Illuminate\Support\Collection::make($currentMetrics)->toArray();

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

    private function ensureMonthlyReportExists(\App\Models\User $user, \Carbon\Carbon $monthStart): void
    {
        $monthEnd = $monthStart->copy()->endOfMonth();
        $periodKey = $monthStart->toDateString();
        
        // Check if report sudah ada
        $existing = MonthlyReport::where('user_id', $user->id)
            ->whereDate('period_month', $periodKey)
            ->first();
        
        if ($existing) {
            return;
        }
        
        // Cari transactions untuk bulan sebelumnya
        $transactions = $user->transactionLogs()
            ->with(['type', 'category', 'sourceWallet', 'destinationWallet'])
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('date')
            ->orderBy('id')
            ->get();
        
        // Skip jika tidak ada transaksi
        if ($transactions->isEmpty()) {
            return;
        }
        
        // Build metrics untuk previous month
        $metrics = $this->buildMonthlyMetrics($transactions);
        $localReport = $this->buildLocalMonthlyReport($transactions, $monthStart, null);
        
        // Generate Gemini summary untuk previous month
        $geminiResult = $this->generateGeminiMonthlyReport($user, $transactions, $localReport, $monthStart, null);
        $geminiReport = $geminiResult['summary'] ?? null;
        $finalReport = $geminiReport ?? $localReport;
        
        // Save report ke database
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
}
