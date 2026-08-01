<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Actions\ProcessTransactionAction;
use App\DTO\AIParseResultMulti;
use App\DTO\MultiTransactionItem;
use App\DTO\MultiTransactionResult;
use App\DTO\ParsedTransaction;
use App\DTO\ResolvedTransaction;
use App\Enums\MultiTransactionErrorCode;
use App\Enums\TransactionSource;
use App\Enums\WalletSide;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\CategoryNotFoundException;
use App\Exceptions\WalletNotFoundException;
use App\Models\AiUsageLog;
use App\Models\TransactionDraft;
use App\Models\User;
use App\Services\AI\AiCredentialManager;
use App\Services\AI\AiParseLogService;
use App\Services\AI\AiPreferenceManager;
use App\Services\AI\AiProviderFactory;
use App\Services\AI\TransactionResolver;
use App\Services\Wallet\WalletResolutionService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class MultiTransactionProcessor
{
    public function __construct(
        private readonly AiPreferenceManager $preferenceManager,
        private readonly AiCredentialManager $credentialManager,
        private readonly AiProviderFactory $providerFactory,
        private readonly TransactionResolver $resolver,
        private readonly AiParseLogService $parseLogService,
        private readonly WalletResolutionService $walletResolution,
        private readonly ProcessTransactionAction $transactionAction,
        private readonly DraftPayloadBuilder $draftBuilder,
    ) {}

    public function process(
        User $user, string $text,
        array $wallets, array $categories, array $activeMemories,
        string $source,
        string $prompt = '',
    ): array {
        $context = $this->resolveMultiContext($user, $text, $wallets, $categories, $activeMemories, $source, $prompt);
        if ($context === null) {
            return ['__fallback_to_single' => true];
        }
        [$preference, $adapter, $credential, $model, $resolvedPrompt] = $context;

        $multiResult = $adapter->parseMultiTransaction(
            prompt: $resolvedPrompt,
            apiKey: $credential->api_key,
            model: $model,
            fallbackText: $text,
        );

        if (! $multiResult->success || empty($multiResult->transactions)) {
            if ($multiResult->isOutOfScope) {
                return [
                    'success' => false,
                    'error_code' => 'OUT_OF_SCOPE',
                    'message' => $multiResult->error ?? 'Maaf Bos, saya hanya bisa membantu mencatat keuangan di Bendaharaku.',
                ];
            }

            return [
                'success' => false,
                'error_code' => 'AI_PARSE_FAILED',
                'message' => '❌ AI Gagal memproses multi-transaksi: '.($multiResult->error ?? 'Format tidak dikenali.'),
            ];
        }

        $usage = $multiResult->usage;
        $totalTokens = (int) ($usage['total'] ?? 0);

        if ($totalTokens > 0) {
            AiUsageLog::create([
                'user_id' => $user->id,
                'provider' => $preference->provider->value,
                'model' => $model,
                'prompt_tokens' => $usage['prompt'] ?? 0,
                'completion_tokens' => $usage['completion'] ?? 0,
                'total_tokens' => $totalTokens,
            ]);
        }

        $autoClear = strtoupper($source) !== 'WEB';
        $results = [];

        foreach ($multiResult->transactions as $idx => $parsed) {
            $results[] = $this->processItem($user, $parsed, $idx, $autoClear, $multiResult, $source);
        }

        $multiTxResult = new MultiTransactionResult(
            results: $results,
            provider: $multiResult->provider,
            model: $multiResult->model,
            confidence: $multiResult->confidence,
        );

        Log::info('MultiTx selesai.', [
            'user_id' => $user->id,
            'summary' => $multiTxResult->summary(),
            'failed' => array_map(
                fn (MultiTransactionItem $i) => [
                    'index' => $i->index,
                    'raw' => $i->raw,
                    'error_code' => $i->errorCode?->value,
                    'reason' => $i->reason,
                ],
                $multiTxResult->failedItems()
            ),
        ]);

        $this->parseLogService->createMultiLog(
            user: $user,
            inputText: $text,
            provider: $multiResult->provider,
            model: $multiResult->model,
            confidence: $multiResult->confidence,
            successCount: $multiTxResult->successCount(),
            totalCount: $multiTxResult->totalCount(),
            usage: $multiResult->usage,
        );

        return [
            'success' => $multiTxResult->hasAnySuccess(),
            'is_multi' => true,
            'multi_result' => $multiTxResult,
        ];
    }

    private function resolveMultiContext(User $user, string $text, array $wallets, array $categories, array $activeMemories, string $source, string $prompt = ''): ?array
    {
        $preference = $this->preferenceManager->resolveActivePreference($user);
        if (! $preference) {
            Log::info("MultiTx: No LLM configured for user #{$user->id}, fallback to single parse.");

            return null;
        }

        $credential = $this->credentialManager->getCredential($user, $preference->provider);
        if (! $credential || blank($credential->api_key) || ! $credential->is_valid) {
            throw new AiConfigurationException("API Key untuk '{$preference->provider->value}' bermasalah.");
        }

        $adapter = $this->providerFactory->make($preference->provider);
        $model = $preference->selected_model ?? $preference->provider->defaultModel();

        return [$preference, $adapter, $credential, $model, $prompt];
    }

    private function processItem(User $user, ParsedTransaction $parsed, int $idx, bool $autoClear, AIParseResultMulti $multiResult, string $source): MultiTransactionItem
    {
        $num = $idx + 1;
        $rawText = $parsed->notes ?? "Transaksi #{$num}";

        $guardError = $this->validateItem($parsed, $num, $rawText, $user);
        if ($guardError !== null) {
            return $guardError;
        }

        try {
            $resolved = $this->resolver->resolve($user, $parsed);

            $threshold = (float) config('bendaharaku.ai.confidence.threshold_auto_clear', 0.85);

            $resolved = new ResolvedTransaction(
                amount: $resolved->amount,
                categoryId: $resolved->categoryId,
                sourceWalletId: $resolved->sourceWalletId,
                destinationWalletId: $resolved->destinationWalletId,
                subject: $resolved->subject ?? $user->name,
                notes: $rawText,
                isCleared: ($multiResult->confidence >= $threshold && $autoClear),
                missingWalletSide: $resolved->missingWalletSide,
            );

            if (! $autoClear || ! $resolved->isCleared) {
                return $this->buildDraftItem($user, $resolved, $parsed, $multiResult, $num, $rawText);
            }

            $log = $this->transactionAction->create(
                data: [
                    'date' => now()->format('Y-m-d'),
                    'category_id' => $resolved->categoryId,
                    'source_wallet_id' => $resolved->sourceWalletId,
                    'destination_wallet_id' => $resolved->destinationWalletId,
                    'amount' => $resolved->amount,
                    'subject' => $resolved->subject,
                    'notes' => $resolved->notes,
                    'is_cleared' => $resolved->isCleared,
                ],
                userId: $user->id,
                sourcePrefix: $source,
                source: TransactionSource::TELEGRAM,
            );

            return MultiTransactionItem::success(
                index: $num,
                transaction: $log->load(['category', 'sourceWallet', 'destinationWallet', 'type']),
                raw: $rawText,
            );

        } catch (Throwable $e) {
            return $this->mapItemError($e, $num, $rawText, $user, $parsed);
        }
    }

    private function validateItem(ParsedTransaction $parsed, int $num, string $rawText, User $user): ?MultiTransactionItem
    {
        $guardError = $this->draftBuilder->validateParsed($parsed);
        if ($guardError !== null) {
            $errorCode = MultiTransactionErrorCode::from($guardError);
            $reason = $guardError === 'INVALID_AMOUNT'
                ? 'Nominal tidak valid atau nol.'
                : 'Kategori tidak terdeteksi oleh AI.';

            Log::warning("MultiTx #{$num}: {$reason}", [
                'user_id' => $user->id,
                'raw' => $rawText,
                'parsed' => (array) $parsed,
            ]);

            return MultiTransactionItem::failed(
                index: $num,
                raw: $rawText,
                errorCode: $errorCode,
                reason: $reason,
            );
        }

        return null;
    }

    private function mapItemError(Throwable $e, int $num, string $rawText, User $user, ParsedTransaction $parsed): MultiTransactionItem
    {
        $errorCode = match (true) {
            $e instanceof WalletNotFoundException => MultiTransactionErrorCode::WALLET_NOT_FOUND,
            $e instanceof CategoryNotFoundException => MultiTransactionErrorCode::CATEGORY_NOT_FOUND,
            $e instanceof InvalidArgumentException && str_contains($e->getMessage(), 'sama') => MultiTransactionErrorCode::SAME_WALLET,
            $e instanceof InvalidArgumentException => MultiTransactionErrorCode::VALIDATION_ERROR,
            $e instanceof RuntimeException => MultiTransactionErrorCode::INSUFFICIENT_BALANCE,
            default => MultiTransactionErrorCode::UNKNOWN_ERROR,
        };

        $reason = $errorCode === MultiTransactionErrorCode::UNKNOWN_ERROR
            ? 'Terjadi error tidak terduga.'
            : $e->getMessage();

        $logLevel = $errorCode === MultiTransactionErrorCode::UNKNOWN_ERROR ? 'error' : 'warning';
        $logContext = [
            'user_id' => $user->id,
            'raw' => $rawText,
            'parsed' => (array) $parsed,
        ];

        if ($errorCode === MultiTransactionErrorCode::UNKNOWN_ERROR) {
            $logContext['exception'] = $e;
        } else {
            $logContext['reason'] = $e->getMessage();
        }

        Log::{$logLevel}("MultiTx #{$num}: {$reason}", $logContext);

        return MultiTransactionItem::failed(index: $num, raw: $rawText, errorCode: $errorCode, reason: $reason);
    }

    private function buildDraftItem(User $user, ResolvedTransaction $resolved, ParsedTransaction $parsed, AIParseResultMulti $multiResult, int $num, string $rawText): MultiTransactionItem
    {
        $allWallets = $user->wallets()->get(['id', 'name', 'group_type']);
        $allCats = $user->categories()->get(['id', 'category_name']);

        $categoryName = $allCats->firstWhere('id', $resolved->categoryId)?->category_name
            ?? $parsed->category;

        $sourceWalletName = $allWallets->firstWhere('id', $resolved->sourceWalletId)?->name;
        $destWalletName = $allWallets->firstWhere('id', $resolved->destinationWalletId)?->name;

        $missingWalletSide = $resolved->missingWalletSide;
        $needsWallet = $missingWalletSide !== null
            ? $missingWalletSide !== WalletSide::None->value
            : ($sourceWalletName !== null && $this->walletResolution->isExternalByName($sourceWalletName))
                || ($destWalletName !== null && $this->walletResolution->isExternalByName($destWalletName));

        $typeKey = $parsed->transactionType?->toTypeKey() ?? 'expense';

        $activeConversationId = $user->conversations()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->latest()
            ->value('id');

        $draft = TransactionDraft::create([
            'user_id' => $user->id,
            'conversation_id' => $activeConversationId,
            'ai_provider' => $multiResult->provider,
            'ai_model' => $multiResult->model,
            'draft_type' => 'single',
            'missing_wallet_side' => $missingWalletSide,
            'status' => 'pending',
            'ai_confidence' => $multiResult->confidence,
            'original_text' => $rawText,
            'expires_at' => now()->addHours(24),
            'payload' => $this->draftBuilder->build(
                resolved: $resolved,
                categoryName: $categoryName,
                sourceWalletName: $sourceWalletName,
                destinationWalletName: $destWalletName,
                subject: $resolved->subject ?? $user->name,
                notes: $rawText,
                typeKey: $typeKey,
                needsWallet: $needsWallet,
            ),
        ]);

        return MultiTransactionItem::successDraft(index: $num, draft: $draft, raw: $rawText);
    }
}
