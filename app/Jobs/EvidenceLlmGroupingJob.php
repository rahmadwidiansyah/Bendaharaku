<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Chat\ChatApplicationService;
use App\Chat\DTOs\ChatContext;
use App\Chat\DTOs\ChatResponse;
use App\Chat\Formatters\WebFormatter;
use App\Chat\Services\ChatResponseConverter;
use App\Enums\ChatPlatform;
use App\Models\ChatMessage;
use App\Models\Evidence;
use App\Models\User;
use App\Services\Evidence\LlmEvidenceGroupingService;
use App\Services\Push\PushGate;
use App\Services\Push\PushPayloadBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class EvidenceLlmGroupingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 45;

    public int $maxExceptions = 2;

    public int $timeout = 240;

    private const OCR_WAIT_SECONDS = 120;

    private const OCR_POLL_DELAY_SECONDS = 3;

    private const AI_RETRY_DELAY_SECONDS = 45;

    private const AI_MAX_RETRIES = 1;

    private const WAITING_MESSAGE = 'Proses struk sedang berjalan (OCR/AI bisa lambat). Mohon tunggu maksimal sekitar 7 menit, jangan kirim ulang.';

    private const AI_RETRY_WAITING_MESSAGE = 'AI sedang sibuk. Mencoba ulang otomatis; mohon tunggu maksimal sekitar 3 menit lagi dan jangan kirim ulang.';

    private float $jobStartedAt = 0.0;

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

        if (! $evidence || ! $user || ! $botMessage) {
            Log::warning('EvidenceLlmGroupingJob: missing models', [
                'evidence_id' => $this->evidenceId,
                'user_id' => $this->userId,
                'bot_message_id' => $this->botMessageId,
            ]);

            return;
        }

        if (! in_array($botMessage->status, ['pending', 'processing'], true)) {
            Log::info('EvidenceLlmGroupingJob: bot message sudah selesai, skip', [
                'bot_message_id' => $botMessage->id,
                'status' => $botMessage->status,
            ]);

            return;
        }

        $this->markProcessing($botMessage);

        // Tunggu OCR selesai tanpa memblokir worker. Job di-release tiap 3s,
        // maksimal 120s — OCR service bisa lambat pada perangkat tertentu.
        if (blank($evidence->ocr_text)) {
            $metadata = $botMessage->metadata ?? [];
            $startedAt = $metadata['ocr_wait_started_at'] ?? null;

            if ($startedAt === null) {
                $botMessage->update([
                    'metadata' => array_merge($metadata, [
                        'ocr_wait_started_at' => now()->toIso8601String(),
                    ]),
                ]);
                $waited = 0;
            } else {
                $waited = (int) Carbon::parse($startedAt)->diffInSeconds(now());
            }

            if ($waited < self::OCR_WAIT_SECONDS) {
                Log::info('EvidenceLlmGroupingJob: OCR belum siap, release', [
                    'evidence_id' => $evidence->id,
                    'waited_seconds' => $waited,
                ]);
                $this->release(self::OCR_POLL_DELAY_SECONDS);

                return;
            }

            Log::warning('EvidenceLlmGroupingJob: OCR text masih kosong setelah tunggu', [
                'evidence_id' => $evidence->id,
                'waited_seconds' => $waited,
            ]);
            $botMessage->update([
                'status' => 'failed',
                'content' => [['type' => 'error', 'message' => 'OCR belum selesai, coba buka Review manual.', 'severity' => 'error']],
                'error_message' => 'OCR timeout',
                'metadata' => array_merge($botMessage->metadata ?? [], [
                    'waiting_message' => null,
                ]),
            ]);
            $evidence->refresh();
            try {
                PushGate::dispatch($user, PushPayloadBuilder::evidenceFailed($user, $evidence, 'OCR belum selesai'));
            } catch (\Throwable $e) {
                Log::warning('Evidence push failed (OCR timeout): '.$e->getMessage());
            }

            return;
        }

        $botMessage->update(['status' => 'processing']);
        $this->jobStartedAt = microtime(true);

        try {
            $result = $groupingService->group($evidence, $user, $this->captionHint);

            if (empty($result['success'])) {
                $metadata = $botMessage->metadata ?? [];
                $retryCount = (int) ($metadata['ai_retry_count'] ?? 0);
                $retryable = (bool) ($result['retryable'] ?? false);

                if ($retryable && $retryCount < self::AI_MAX_RETRIES) {
                    $botMessage->update([
                        'status' => 'processing',
                        'content' => [],
                        'error_message' => null,
                        'metadata' => array_merge($metadata, [
                            'evidence_uuid' => $evidence->uuid,
                            'ai_retry_count' => $retryCount + 1,
                            'last_error_code' => $result['error_code'] ?? 'GROUPING_FAILED',
                            'waiting_message' => self::AI_RETRY_WAITING_MESSAGE,
                        ]),
                    ]);
                    Log::warning('EvidenceLlmGroupingJob: retryable failure, requeue', [
                        'evidence_id' => $evidence->id,
                        'error_code' => $result['error_code'] ?? null,
                        'retry_count' => $retryCount + 1,
                    ]);
                    $this->release(self::AI_RETRY_DELAY_SECONDS);

                    return;
                }

                $botMessage->update([
                    'status' => 'failed',
                    'content' => [['type' => 'error', 'message' => $result['message'] ?? 'Gagal mengelompokkan struk.', 'severity' => 'error']],
                    'error_message' => $result['message'] ?? 'Grouping failed',
                    'metadata' => array_merge($metadata, [
                        'evidence_uuid' => $evidence->uuid,
                        'error_code' => $result['error_code'] ?? 'GROUPING_FAILED',
                        'waiting_message' => null,
                    ]),
                ]);
                $this->updateUserImageStatus($botMessage->conversation_id, $evidence->uuid, 'FAILED');
                // Jika fail karena INVALID_AMOUNT (token abis, LLM gagal ekstrak nominal 0), coba fallback ke parsed amount evidence
                if (($result['error_code'] ?? '') === 'INVALID_AMOUNT' || str_contains($result['message'] ?? '', 'Nominal tidak valid')) {
                    Log::warning('EvidenceLlmGroupingJob: fallback ke parsed amount karena LLM amount 0', ['evidence_id' => $evidence->id, 'parsed_amount' => $evidence->parsed_data?->amount ?? $evidence->amount ?? null]);
                }
                $evidence->refresh();
                try {
                    PushGate::dispatch($user, PushPayloadBuilder::evidenceFailed($user, $evidence, $result['message'] ?? ''));
                } catch (\Throwable $e) {
                    Log::warning('Evidence push failed (grouping): '.$e->getMessage());
                }

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

            // Update user bubble status dari "Memproses" jadi "Siap" biar tidak stuck di "Memproses" meski sukses (request user)
            // Cari user message yang mengandung evidence_uuid (lebih reliable dari LIKE JSON)
            $userMessage = ChatMessage::where('conversation_id', $botMessage->conversation_id)
                ->where('role', 'user')
                ->latest('id')
                ->get()
                ->first(function ($msg) use ($evidence) {
                    foreach ($msg->content ?? [] as $c) {
                        if (($c['type'] ?? '') === 'image' && (($c['evidenceUuid'] ?? $c['evidence_uuid'] ?? '') === $evidence->uuid)) {
                            return true;
                        }
                    }

                    return false;
                });
            if ($userMessage) {
                $content = $userMessage->content;
                $changed = false;
                foreach ($content as &$c) {
                    if (($c['type'] ?? '') === 'image' && (($c['evidenceUuid'] ?? $c['evidence_uuid'] ?? '') === $evidence->uuid)) {
                        $c['evidenceStatus'] = 'READY';
                        $c['evidence_status'] = 'READY';
                        $changed = true;
                    }
                }
                unset($c);
                if ($changed) {
                    $userMessage->update(['content' => $content]);
                }
            }

            Log::info('EvidenceLlmGroupingJob: completed', [
                'evidence_id' => $evidence->id,
                'user_id' => $user->id,
                'is_multi' => $result['is_multi'] ?? false,
                'intent' => $chatResponse->intent->value,
            ]);
            // Push grouping selesai (styling) — tampil walau user active & walau app tidak dibuka (SW)
            $evidence->refresh();
            try {
                PushGate::dispatch($user, PushPayloadBuilder::evidenceReady($user, $evidence));
            } catch (\Throwable $e) {
                Log::warning('Evidence push failed (ready): '.$e->getMessage());
            }
        } catch (\Throwable $e) {
            Log::error('EvidenceLlmGroupingJob: failed', [
                'evidence_id' => $evidence->id,
                'error' => $e->getMessage(),
            ]);
            $botMessage->update([
                'status' => 'failed',
                'content' => [['type' => 'error', 'message' => 'Gagal memproses struk via LLM: '.$e->getMessage(), 'severity' => 'error']],
                'error_message' => $e->getMessage(),
                'metadata' => array_merge($botMessage->metadata ?? [], [
                    'waiting_message' => null,
                ]),
            ]);
            $this->updateUserImageStatus($botMessage->conversation_id, $evidence->uuid, 'FAILED');
            try {
                $freshEvidence = Evidence::find($evidence->id);
                if ($freshEvidence) {
                    PushGate::dispatch($user, PushPayloadBuilder::evidenceFailed($user, $freshEvidence, $e->getMessage()));
                }
            } catch (\Throwable $pe) {
                Log::warning('Evidence push failed (exception): '.$pe->getMessage());
            }
        }
    }

    /**
     * Tandai pesan bot sedang diproses + pasang pesan tunggu.
     * Tidak menimpa pesan retry yang sudah ada (mis. "AI sedang sibuk...").
     */
    private function markProcessing(ChatMessage $botMessage): void
    {
        $metadata = $botMessage->metadata ?? [];

        if (empty($metadata['waiting_message'])) {
            $botMessage->update([
                'status' => 'processing',
                'metadata' => array_merge($metadata, [
                    'waiting_message' => self::WAITING_MESSAGE,
                ]),
            ]);

            return;
        }

        if ($botMessage->status !== 'processing') {
            $botMessage->update(['status' => 'processing']);
        }
    }

    /**
     * Dipanggil saat job timeout / worker mati. Tanpa ini, bot message bisa
     * tertahan di status processing sampai WebChatController menandainya stuck.
     */
    public function failed(?\Throwable $exception): void
    {
        $botMessage = ChatMessage::find($this->botMessageId);

        if (! $botMessage || ! in_array($botMessage->status, ['pending', 'processing'], true)) {
            return;
        }

        $message = 'Gagal memproses struk (timeout). Silakan coba lagi.';
        $botMessage->update([
            'status' => 'failed',
            'content' => [['type' => 'error', 'message' => $message, 'severity' => 'error']],
            'error_message' => $exception?->getMessage() ?: 'Evidence grouping timeout',
            'metadata' => array_merge($botMessage->metadata ?? [], [
                'waiting_message' => null,
            ]),
        ]);

        $evidence = Evidence::find($this->evidenceId);
        if (! $evidence) {
            return;
        }

        $this->updateUserImageStatus($botMessage->conversation_id, $evidence->uuid, 'FAILED');

        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        try {
            PushGate::dispatch($user, PushPayloadBuilder::evidenceFailed($user, $evidence, $message));
        } catch (\Throwable $e) {
            Log::warning('Evidence push failed (job failed hook): '.$e->getMessage());
        }
    }

    private function updateUserImageStatus(int $conversationId, string $uuid, string $status): void
    {
        $userMessage = ChatMessage::where('conversation_id', $conversationId)
            ->where('role', 'user')
            ->latest('id')
            ->get()
            ->first(function ($msg) use ($uuid) {
                foreach ($msg->content ?? [] as $c) {
                    if (($c['type'] ?? '') === 'image' && (($c['evidenceUuid'] ?? $c['evidence_uuid'] ?? '') === $uuid)) {
                        return true;
                    }
                }

                return false;
            });
        if (! $userMessage) {
            return;
        }
        $content = $userMessage->content;
        $changed = false;
        foreach ($content as &$c) {
            if (($c['type'] ?? '') === 'image' && (($c['evidenceUuid'] ?? $c['evidence_uuid'] ?? '') === $uuid)) {
                $c['evidenceStatus'] = $status;
                $c['evidence_status'] = $status;
                $changed = true;
            }
        }
        unset($c);
        if ($changed) {
            $userMessage->update(['content' => $content]);
        }
    }

    private function buildChatResponseFromResult(array $result, ChatContext $context): ChatResponse
    {
        $converter = app(ChatResponseConverter::class);
        $multi = $result['multi_result'] ?? null;
        $usage = $result['usage'] ?? ($multi?->usage ?? []);
        $totalTokens = $usage['total'] ?? null;
        // Extract provider/model/confidence dari top-level atau dari multi_result (untuk struk multi-item)
        $provider = $result['provider'] ?? $multi?->provider ?? null;
        $model = $result['model'] ?? $multi?->model ?? null;
        $confidence = $result['confidence'] ?? $multi?->confidence ?? null;
        $latency = $this->jobStartedAt > 0 ? (int) round((microtime(true) - $this->jobStartedAt) * 1000) : 0;

        $metadata = [
            'trace_id' => $context->traceId,
            'platform' => $context->platform->value,
            'provider' => $provider,
            'model' => $model,
            'confidence' => $confidence,
            'latency_ms' => $latency,
            'total_tokens' => $totalTokens,
            'usage' => $usage,
            'evidence_uuid' => $context->metadata['evidence_uuid'] ?? null,
        ];

        if (! empty($result['is_multi']) && isset($result['multi_result'])) {
            return $converter->convertMultiResult($result['multi_result'], $context, $metadata);
        }

        if (! empty($result['success']) && isset($result['transaction'])) {
            return $converter->convertSingleSuccess($result, $context, $metadata, $context->metadata['caption_hint'] ?? '');
        }

        if (empty($result['success'])) {
            return $converter->convertSingleFailure($result, $metadata);
        }

        // Fallback
        return ChatResponse::singleSuccess([], $metadata);
    }
}
