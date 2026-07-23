<?php

declare(strict_types=1);

namespace App\Chat;

use App\Chat\DTOs\ChatRequest;
use App\Chat\DTOs\ChatResponse;
use App\Chat\Errors\ErrorDetail;
use App\Chat\Services\ChatResponseConverter;
use App\Chat\Services\CommandRouter;
use App\Chat\Services\DraftViewModelBuilder;
use App\Enums\ChatErrorSeverity;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiTokenLimitException;
use App\Exceptions\CategoryNotFoundException;
use App\Exceptions\WalletNotFoundException;
use App\Models\User;
use App\Services\Chat\ChatTransactionOrchestrator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class ChatApplicationService
{
    public function __construct(
        private readonly ChatTransactionOrchestrator $orchestrator,
        private readonly CommandRouter $commandRouter,
        private readonly ChatResponseConverter $responseConverter,
    ) {}

    public function handleMessage(ChatRequest $request): ChatResponse
    {
        $context = $request->context;
        $user = $request->user;
        $text = $request->normalizedMessage();
        $source = $context->sourcePrefix();
        $startTime = microtime(true);

        Log::info('ChatApplicationService: processing message', [
            'trace_id' => $context->traceId,
            'platform' => $context->platform->value,
            'user_id' => $user->id,
            'length' => strlen($text),
        ]);

        $commandResponse = $this->commandRouter->route($text, $user, $context, $startTime);
        if ($commandResponse !== null) {
            return $commandResponse;
        }

        try {
            $result = $this->orchestrator->process($user, $text, $source);

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $metadata = $this->responseConverter->buildMetadata($result, $context, $latency);

            if (! empty($result['is_multi'])) {
                return $this->responseConverter->convertMultiResult($result['multi_result'], $context, $metadata);
            }

            if (! $result['success']) {
                return $this->responseConverter->convertSingleFailure($result, $metadata);
            }

            return $this->responseConverter->convertSingleSuccess($result, $context, $metadata, $text);

        } catch (AiConfigurationException $e) {
            return $this->responseConverter->failureResponse([ErrorDetail::aiNotConfigured()], $context, $startTime);

        } catch (AiRateLimitException $e) {
            return $this->responseConverter->failureResponse([ErrorDetail::aiRateLimit($e->getMessage())], $context, $startTime);

        } catch (AiTimeoutException $e) {
            return $this->responseConverter->failureResponse([ErrorDetail::aiTimeout($e->getMessage())], $context, $startTime);

        } catch (AiTokenLimitException $e) {
            return $this->responseConverter->failureResponse([
                ErrorDetail::aiTokenLimit($e->getProvider(), $e->getEstimatedTokens()),
            ], $context, $startTime);

        } catch (AiProviderException $e) {
            return $this->responseConverter->failureResponse([ErrorDetail::aiProviderError($e->getMessage(), $e->getMessage())], $context, $startTime);

        } catch (CategoryNotFoundException|WalletNotFoundException $e) {
            $error = str_contains($e->getMessage(), 'ategori')
                ? ErrorDetail::categoryNotFound($e->getMessage())
                : ErrorDetail::walletNotFound($e->getMessage());

            return $this->responseConverter->failureResponse([$error], $context, $startTime);

        } catch (ModelNotFoundException $e) {
            return $this->responseConverter->failureResponse([
                new ErrorDetail(
                    code: 'DATA_NOT_FOUND',
                    messageKey: 'chat.error.data_not_found_single',
                    severity: ChatErrorSeverity::Error,
                ),
            ], $context, $startTime);

        } catch (InvalidArgumentException|\RuntimeException $e) {
            return $this->responseConverter->failureResponse([
                new ErrorDetail(
                    code: 'VALIDATION_ERROR',
                    messageKey: 'chat.error.runtime',
                    params: ['message' => $e->getMessage()],
                    severity: ChatErrorSeverity::Error,
                ),
            ], $context, $startTime);

        } catch (Throwable $e) {
            Log::error('ChatApplicationService: unhandled exception', [
                'trace_id' => $context->traceId,
                'user_id' => $user->id,
                'exception' => $e,
            ]);

            return $this->responseConverter->failureResponse([ErrorDetail::systemError()], $context, $startTime);
        }
    }
}
