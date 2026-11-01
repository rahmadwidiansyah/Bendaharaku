<?php

declare(strict_types=1);

namespace App\Chat;

use App\Chat\DTOs\ChatRequest;
use App\Chat\DTOs\ChatResponse;
use App\Chat\Errors\ErrorDetail;
use App\Chat\Services\ChatResponseConverter;
use App\Chat\Services\CommandRouter;
use App\Enums\ChatErrorSeverity;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiProviderException;
use App\Exceptions\AiRateLimitException;
use App\Exceptions\AiTimeoutException;
use App\Exceptions\AiTokenLimitException;
use App\Exceptions\CategoryNotFoundException;
use App\Exceptions\WalletNotFoundException;
use App\Services\AI\Assistant\AssistantConversationPipeline;
use App\Services\AI\Assistant\AssistantReply;
use App\Services\AI\Assistant\ChatIntentClassifier;
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
        private readonly ChatIntentClassifier $intentClassifier,
        private readonly AssistantConversationPipeline $conversationPipeline,
        private readonly AssistantReply $assistantReply,
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
        // Log inference path before orchestrator
        Log::debug('[PIPELINE:ASP] handleMessage inference path', [
            'trace_id' => $context->traceId,
            'current_class' => __CLASS__,
            'current_method' => '__invoke',
            'source' => $source,
            'platform' => $context->platform->value,
            'is_command' => $commandResponse !== null,
        ]);

        if ($commandResponse !== null) {
            return $commandResponse;
        }

        try {
            // Lapis C: klasifikasi intent sebelum orchestrator. Pesan percakapan/
            // pertanyaan tidak boleh masuk jalur parsing transaksi.
            $classified = $this->intentClassifier->classify($user, $text);

            Log::debug('[PIPELINE:CAS] intent classified', [
                'trace_id' => $context->traceId,
                'intent' => $classified['intent'] ?? null,
                'confidence' => $classified['confidence'] ?? null,
                'source' => $classified['source'] ?? null,
            ]);

            if (in_array($classified['intent'] ?? 'transaction', ['conversation', 'question'], true)) {
                return $this->conversationPipeline->handle(
                    user: $user,
                    text: $text,
                    intent: (string) $classified['intent'],
                    context: $context,
                    startTime: $startTime,
                );
            }

            $result = $this->orchestrator->process($user, $text, $source);

            Log::debug('[PIPELINE:CAS] orchestrator->process result', [
                'trace_id' => $context->traceId,
                'success' => $result['success'] ?? null,
                'is_web_draft' => $result['is_web_draft'] ?? false,
                'error_code' => $result['error_code'] ?? null,
                'has_draft' => isset($result['draft']),
                'has_trx' => isset($result['transaction']),
            ]);

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $metadata = $this->responseConverter->buildMetadata($result, $context, $latency);

            // Pesan natural (lapis D) SETELAH transaksi/draft tersimpan.
            // Gagal di sini tidak pernah membatalkan transaksi; converter
            // kembali ke perilaku lama bila hasilnya null.
            $assistantMessage = $this->shouldGenerateNaturalReply($result)
                ? $this->assistantReply->forTransaction($user, $result, $context->locale)
                : null;

            if (! empty($result['is_multi'])) {
                return $this->responseConverter->convertMultiResult(
                    $result['multi_result'],
                    $context,
                    $metadata,
                    $assistantMessage,
                );
            }

            if (! $result['success']) {
                Log::debug('[PIPELINE:CAS] convertSingleFailure', [
                    'trace_id' => $context->traceId,
                    'error_code' => $result['error_code'] ?? null,
                    'message' => $result['message'] ?? null,
                ]);

                return $this->responseConverter->convertSingleFailure($result, $metadata);
            }

            Log::debug('[PIPELINE:CAS] convertSingleSuccess', ['trace_id' => $context->traceId]);

            return $this->responseConverter->convertSingleSuccess($result, $context, $metadata, $text, $assistantMessage);

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

    private function shouldGenerateNaturalReply(array $result): bool
    {
        if (! (bool) config('bendaharaku.ai.assistant.natural_reply', true)) {
            return false;
        }

        if (! empty($result['is_multi'])) {
            return (bool) (($result['multi_result'] ?? null)?->hasAnySuccess() ?? false);
        }

        if (! empty($result['is_web_draft'])) {
            return true;
        }

        return ! empty($result['success']) && isset($result['transaction']);
    }
}
