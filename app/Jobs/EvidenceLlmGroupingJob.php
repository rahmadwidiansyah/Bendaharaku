<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Chat\ChatApplicationService;
use App\Chat\DTOs\ChatContext;
use App\Chat\DTOs\ChatRequest;
use App\Chat\Formatters\WebFormatter;
use App\Enums\ChatPlatform;
use App\Models\ChatMessage;
use App\Models\Evidence;
use App\Models\User;
use App\Services\Evidence\LlmEvidenceGroupingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EvidenceLlmGroupingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(
        public int $evidenceId,
        public int $userId,
        public int $botMessageId,
        public string $captionHint = '',
    ) {}

    public function handle(
        LlmEvidenceGroupingService $groupingService,
        WebFormatter $formatter,
    ): void {
        $evidence = Evidence::find($this->evidenceId);
        $user = User::find($this->userId);
        $botMessage = ChatMessage::find($this->botMessageId);

        if (!$evidence || !$user || !$botMessage) {
            Log::warning('EvidenceLlmGroupingJob: missing models', [
                'evidence_id' => $this->evidenceId,
                'user_id' => $this->userId,
                'bot_message_id' => $this->botMessageId,
            ]);
            return;
        }

        // Tunggu OCR selesai (max 30s) — karena EvidenceLlmGroupingJob dispatch segera setelah upload, OCR mungkin belum siap
        $waited = 0;
        while (blank($evidence->ocr_text) && $waited < 30) {
            sleep(2);
            $waited += 2;
            $evidence->refresh();
        }

        if (blank($evidence->ocr_text)) {
            Log::warning('EvidenceLlmGroupingJob: OCR text masih kosong setelah tunggu', ['evidence_id' => $evidence->id]);
            $botMessage->update([
                'status' => 'failed',
                'content' => [['type' => 'error', 'message' => 'OCR belum selesai, coba buka Review manual.', 'severity' => 'error']],
                'error_message' => 'OCR timeout',
            ]);
            return;
        }

        $botMessage->update(['status' => 'processing']);

        try {
            $result = $groupingService->group($evidence, $user, $this->captionHint);

            if (empty($result['success'])) {
                $botMessage->update([
                    'status' => 'failed',
                    'content' => [['type' => 'error', 'message' => $result['message'] ?? 'Gagal mengelompokkan struk.', 'severity' => 'error']],
                    'error_message' => $result['message'] ?? 'Grouping failed',
                    'metadata' => array_merge($botMessage->metadata ?? [], ['evidence_uuid' => $evidence->uuid, 'error_code' => $result['error_code'] ?? 'GROUPING_FAILED']),
                ]);
                return;
            }

            // Sukses: build ChatResponse via orchestrator sudah di dalam groupingService (via ChatTransactionOrchestrator)
            // Result berisi is_multi + multi_result atau single transaction
            // Kita perlu format via WebFormatter seperti ProcessChatMessageJob
            $locale = $user->locale ?? 'id';
            $context = ChatContext::make(
                platform: ChatPlatform::Web,
                conversationId: (string) $botMessage->conversation_id,
                locale: $locale,
                timezone: $user->timezone ?? 'Asia/Jakarta',
                sessionId: (string) $botMessage->conversation_id,
                metadata: ['evidence_uuid' => $evidence->uuid, 'caption_hint' => $this->captionHint],
            );

            // Reuse ChatApplicationService formatting? Simpler: langsung pakai result dari groupingService
            // Jika is_multi, result sudah punya multi_result yang perlu diformat via ChatResponseConverter
            // Kita panggil ChatApplicationService untuk format? Atau manual via WebFormatter
            // Untuk sekarang, kita buat ChatResponse dari result dan format

            $chatResponse = $this->buildChatResponseFromResult($result, $context);

            $formatted = $formatter->format($chatResponse, $context);

            $botMessage->update([
                'status' => 'completed',
                'content' => $formatted['components'],
                'raw_text' => $evidence->ocr_text ? mb_substr($evidence->ocr_text, 0, 200) : null,
                'metadata' => array_merge($formatted['metadata'] ?? [], [
                    'evidence_uuid' => $evidence->uuid,
                    'caption_hint' => $this->captionHint,
                    'intent' => $chatResponse->intent->value,
                    'success' => $chatResponse->success,
                ]),
                'error_message' => null,
            ]);

            Log::info('EvidenceLlmGroupingJob: completed', [
                'evidence_id' => $evidence->id,
                'user_id' => $user->id,
                'is_multi' => $result['is_multi'] ?? false,
                'intent' => $chatResponse->intent->value,
            ]);
        } catch (\Throwable $e) {
            Log::error('EvidenceLlmGroupingJob: failed', [
                'evidence_id' => $evidence->id,
                'error' => $e->getMessage(),
            ]);
            $botMessage->update([
                'status' => 'failed',
                'content' => [['type' => 'error', 'message' => 'Gagal memproses struk via LLM: ' . $e->getMessage(), 'severity' => 'error']],
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function buildChatResponseFromResult(array $result, \App\Chat\DTOs\ChatContext $context): \App\Chat\DTOs\ChatResponse
    {
        // Jika result sudah berupa ChatResponse (dari orchestrator), kembalikan langsung
        // Tapi orchestrator mengembalikan array, bukan ChatResponse untuk multi
        // Kita perlu convert via ChatResponseConverter
        $converter = app(\App\Chat\Services\ChatResponseConverter::class);
        $metadata = [
            'trace_id' => $context->traceId,
            'platform' => $context->platform->value,
            'provider' => $result['provider'] ?? null,
            'model' => $result['model'] ?? null,
            'confidence' => $result['confidence'] ?? null,
            'latency_ms' => 0,
            'evidence_uuid' => $context->metadata['evidence_uuid'] ?? null,
        ];

        if (!empty($result['is_multi']) && isset($result['multi_result'])) {
            return $converter->convertMultiResult($result['multi_result'], $context, $metadata);
        }

        if (!empty($result['success']) && isset($result['transaction'])) {
            return $converter->convertSingleSuccess($result, $context, $metadata, $context->metadata['caption_hint'] ?? '');
        }

        if (empty($result['success'])) {
            return $converter->convertSingleFailure($result, $metadata);
        }

        // Fallback
        return \App\Chat\DTOs\ChatResponse::singleSuccess([], $metadata);
    }
}
