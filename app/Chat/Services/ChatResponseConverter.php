<?php

declare(strict_types=1);

namespace App\Chat\Services;

use App\Chat\Components\DividerComponent;
use App\Chat\Components\ErrorComponent;
use App\Chat\Components\SummaryCardComponent;
use App\Chat\Components\TextComponent;
use App\Chat\Components\TransactionCardComponent;
use App\Chat\Components\WarningComponent;
use App\Chat\DTOs\ChatContext;
use App\Chat\DTOs\ChatResponse;
use App\Chat\Errors\ErrorDetail;
use App\DTO\MultiTransactionItem;
use App\DTO\MultiTransactionResult;
use App\Enums\ChatErrorSeverity;
use App\Models\TransactionDraft;

class ChatResponseConverter
{
    public function __construct(
        private readonly DraftViewModelBuilder $draftViewModelBuilder,
    ) {}

    public function convertSingleSuccess(
        array $result,
        ChatContext $context,
        array $metadata,
        string $originalText,
    ): ChatResponse {
        if (! empty($result['is_web_draft'])) {
            return $this->convertWebDraftSuccess($result, $context, $metadata);
        }

        $trx = $result['transaction'];
        $isCleared = $trx->is_cleared;

        $components = [];

        $components[] = new TransactionCardComponent(
            transaction: $trx,
            showDetails: true,
        );

        if (! $isCleared && $trx->sourceWallet?->group_type === 'System') {
            $components[] = new WarningComponent(
                messageKey: 'chat.wallet.missing_choose',
            );
        }

        $components[] = new DividerComponent;
        $components[] = new TextComponent(
            translationKey: 'chat.transaction.label_original_msg',
        );
        $components[] = new TextComponent(
            translationKey: 'chat.transaction.label_ai_provider',
            params: [
                'provider' => $metadata['provider'] ?? '',
                'confidence' => isset($metadata['confidence'])
                    ? round($metadata['confidence'] * 100).'%'
                    : '-',
            ],
        );

        if ($isCleared) {
            return ChatResponse::singleSuccess($components, $metadata);
        }

        return ChatResponse::draft($components, $metadata);
    }

    public function convertWebDraftSuccess(
        array $result,
        ChatContext $context,
        array $metadata,
    ): ChatResponse {
        $draft = $result['draft'];
        $payload = $draft->payload ?? [];

        $fakeTrx = $this->draftViewModelBuilder->buildFakeTransactionFromPayload($payload, $draft->missing_wallet_side);

        $needsWallet = (bool) ($payload['needs_wallet'] ?? false);

        $components = [];

        $components[] = new TransactionCardComponent(
            transaction: $fakeTrx,
            showDetails: true,
            draftId: $draft->id,
        );

        if ($needsWallet) {
            $messageKey = match ($draft->missing_wallet_side) {
                'SOURCE' => 'chat.wallet.missing_source',
                'DESTINATION' => 'chat.wallet.missing_destination',
                default => 'chat.wallet.missing_choose',
            };
            $components[] = new WarningComponent(messageKey: $messageKey);
        }

        $components[] = new DividerComponent;
        $components[] = new TextComponent(
            translationKey: 'chat.transaction.label_original_msg',
        );
        $components[] = new TextComponent(
            translationKey: 'chat.transaction.label_ai_provider',
            params: [
                'provider' => $metadata['provider'] ?? '',
                'confidence' => isset($metadata['confidence'])
                    ? round($metadata['confidence'] * 100).'%'
                    : '-',
            ],
        );

        return ChatResponse::draft($components, $metadata);
    }

    public function convertSingleFailure(array $result, array $metadata): ChatResponse
    {
        $errorCode = $result['error_code'] ?? '';
        $message = $result['message'] ?? '';

        $error = match ($errorCode) {
            'WALLET_NOT_FOUND' => ErrorDetail::walletNotFound($message),
            'CATEGORY_NOT_FOUND' => ErrorDetail::categoryNotFound($message),
            'INVALID_AMOUNT' => new ErrorDetail('INVALID_AMOUNT', 'chat.validation.invalid_amount'),
            'MISSING_SUBJECT' => new ErrorDetail('MISSING_SUBJECT', 'chat.validation.missing_debt_subject'),
            'DRAFT_SAVED' => new ErrorDetail('DRAFT_SAVED', 'chat.transaction.draft_saved', severity: ChatErrorSeverity::Warning),
            'AI_PARSE_FAILED' => new ErrorDetail('AI_PARSE_FAILED', 'chat.ai.parse_failed', params: ['reason' => trans('chat.ai.parse_failed_default')]),
            'SETUP_FAILED', 'VALIDATION_ERROR' => new ErrorDetail('VALIDATION_ERROR', 'chat.error.system', params: ['message' => $message]),
            'AI_NOT_CONFIGURED' => ErrorDetail::aiNotConfigured(),
            'AI_RATE_LIMIT' => ErrorDetail::aiRateLimit($message),
            'AI_TIMEOUT' => ErrorDetail::aiTimeout($message),
            'AI_PROVIDER_ERROR' => ErrorDetail::aiProviderError($message, $message),
            'DATA_NOT_FOUND' => new ErrorDetail('DATA_NOT_FOUND', 'chat.error.data_not_found_single', severity: ChatErrorSeverity::Error),
            default => ErrorDetail::systemError(),
        };

        return ChatResponse::failure([$error], [], $metadata);
    }

    public function convertMultiResult(
        MultiTransactionResult $multiResult,
        ChatContext $context,
        array $metadata,
    ): ChatResponse {
        $components = [];

        $components[] = new SummaryCardComponent(
            total: $multiResult->totalCount(),
            success: $multiResult->successCount(),
            failed: $multiResult->failedCount(),
            confidence: $multiResult->confidence,
        );

        $components[] = new DividerComponent;

        foreach ($multiResult->results as $item) {
            if ($item->isSuccess()) {
                if ($item->isDraft()) {
                    $draft = $item->draft;
                    $payload = $draft->payload ?? [];
                    $fakeTrx = $this->draftViewModelBuilder->buildFakeTransactionFromPayload($payload, $draft->missing_wallet_side);

                    $components[] = new TransactionCardComponent(
                        transaction: $fakeTrx,
                        index: $item->index,
                        showDetails: false,
                        draftId: $draft->id,
                    );
                } else {
                    $components[] = new TransactionCardComponent(
                        transaction: $item->transaction,
                        index: $item->index,
                        showDetails: false,
                    );
                }
            } else {
                $components[] = new ErrorComponent(
                    messageKey: $this->mapErrorCodeToKey($item->errorCode?->value),
                    params: $this->extractErrorParams($item->reason ?? '', $item->errorCode?->value),
                    raw: $item->raw,
                    index: $item->index,
                    severity: ChatErrorSeverity::Error,
                    recoverable: true,
                );
            }
        }

        $components[] = new DividerComponent;
        $components[] = new TextComponent(
            translationKey: 'chat.transaction.label_ai_provider',
            params: [
                'provider' => $metadata['provider'] ?? '',
                'confidence' => isset($metadata['confidence'])
                    ? round($metadata['confidence'] * 100).'%'
                    : '-',
            ],
        );

        return ChatResponse::multiResult(
            hasAnySuccess: $multiResult->hasAnySuccess(),
            components: $components,
            metadata: $metadata,
        );
    }

    public function failureResponse(
        array $errors,
        ChatContext $context,
        float $startTime,
    ): ChatResponse {
        $latency = (int) round((microtime(true) - $startTime) * 1000);

        return ChatResponse::failure($errors, [], [
            'trace_id' => $context->traceId,
            'platform' => $context->platform->value,
            'latency_ms' => $latency,
        ]);
    }

    public function buildMetadata(array $result, ChatContext $context, int|float $latency): array
    {
        $usage = $result['usage'] ?? ($result['multi_result']?->usage ?? []);
        $totalTokens = $usage['total'] ?? null;

        return [
            'trace_id' => $context->traceId,
            'platform' => $context->platform->value,
            'provider' => $result['provider'] ?? ($result['multi_result']?->provider ?? null),
            'model' => $result['model'] ?? ($result['multi_result']?->model ?? null),
            'confidence' => $result['confidence'] ?? ($result['multi_result']?->confidence ?? null),
            'latency_ms' => (int) $latency,
            'total_tokens' => $totalTokens,
        ];
    }

    public function mapErrorCodeToKey(?string $errorCode): string
    {
        return match ($errorCode) {
            'WALLET_NOT_FOUND' => 'chat.wallet.not_found',
            'CATEGORY_NOT_FOUND' => 'chat.category.not_found',
            'INVALID_AMOUNT' => 'chat.validation.invalid_amount',
            'SAME_WALLET' => 'chat.validation.same_wallet',
            'INSUFFICIENT_BALANCE' => 'chat.wallet.insufficient',
            'VALIDATION_ERROR' => 'chat.validation.invalid_amount',
            default => 'chat.error.system',
        };
    }

    public function extractErrorParams(string $reason, ?string $errorCode): array
    {
        if (in_array($errorCode, ['WALLET_NOT_FOUND', 'CATEGORY_NOT_FOUND'])) {
            if (preg_match("/['\"]([^'\"]+)['\"]/", $reason, $m)) {
                return ['name' => $m[1]];
            }
        }

        return ['message' => $reason];
    }
}
