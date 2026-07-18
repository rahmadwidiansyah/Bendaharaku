<?php

declare(strict_types=1);

namespace App\Chat;

use App\Chat\DTOs\ChatRequest;
use App\Chat\DTOs\ChatResponse;
use App\Chat\DTOs\ChatContext;
use App\Chat\Components\TextComponent;
use App\Chat\Components\DividerComponent;
use App\Chat\Components\TransactionCardComponent;
use App\Chat\Components\SummaryCardComponent;
use App\Chat\Components\ErrorComponent;
use App\Chat\Components\SuggestionComponent;
use App\Chat\Errors\ErrorDetail;
use App\Enums\ChatErrorSeverity;
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
use Illuminate\Support\Facades\Log;
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

        try {
            $result = $this->orchestrator->process($user, $text, $source);

            $latency  = round((microtime(true) - $startTime) * 1000);
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
        $latency = round((microtime(true) - $startTime) * 1000);
        return ChatResponse::failure($errors, [], [
            'trace_id' => $context->traceId,
            'platform' => $context->platform->value,
            'latency'  => $latency,
        ]);
    }

    private function buildMetadata(array $result, ChatContext $context, int $latency): array
    {
        return array_filter([
            'trace_id'   => $context->traceId,
            'platform'   => $context->platform->value,
            'provider'   => $result['provider'] ?? ($result['multi_result']?->provider ?? null),
            'model'      => $result['model'] ?? ($result['multi_result']?->model ?? null),
            'confidence' => $result['confidence'] ?? ($result['multi_result']?->confidence ?? null),
            'latency_ms' => $latency,
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
}
