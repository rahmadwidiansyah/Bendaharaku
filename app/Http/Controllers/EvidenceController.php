<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Evidence\Events\EvidenceUploaded;
use App\Evidence\Jobs\ProcessEvidenceJob;
use App\Http\Requests\StoreEvidenceRequest;
use App\Jobs\EvidenceLlmGroupingJob;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Evidence;
use App\Services\Evidence\EvidencePipelineService;
use App\Services\Evidence\EvidenceUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EvidenceController extends Controller
{
    public function __construct(
        private readonly EvidenceUploadService $uploadService,
        private readonly EvidencePipelineService $pipelineService,
    ) {}

    public function store(StoreEvidenceRequest $request): JsonResponse
    {
        try {
            $file = $request->file('image');
            $user = $request->user();

            $result = $this->uploadService->upload(
                user: $user,
                file: $file,
                source: Evidence::SOURCE_CHAT_UPLOAD,
            );

            $evidence = $result['evidence'];
            // Pakai accessor model yang sudah route ke chat.evidence.image agar foto langsung tampil
            $url = $evidence->url;

            Log::info('Evidence uploaded', [
                'user_id' => $user->id,
                'evidence_id' => $evidence->id,
                'uuid' => $evidence->uuid,
                'original_name' => $evidence->original_name,
                'size' => $evidence->size,
            ]);

            event(new EvidenceUploaded($evidence));

            $this->pipelineService->queue($evidence);
            ProcessEvidenceJob::dispatch($evidence->id);

            return response()->json([
                'success' => true,
                'evidence' => [
                    'uuid' => $evidence->uuid,
                    'url' => $url,
                    'original_name' => $evidence->original_name,
                    'mime_type' => $evidence->mime_type,
                    'size' => $evidence->size,
                    'formatted_size' => $evidence->formatted_size,
                    'status' => $evidence->status->value,
                    'processing' => ! $evidence->status->isTerminal(),
                    'created_at' => $evidence->created_at->toIso8601String(),
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Evidence upload failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('evidence.upload_failed'),
            ], 500);
        }
    }

    /**
     * GET /chat/evidence/{uuid}/image
     * Serve private evidence file dengan auth check.
     * Dipakai ChatMessage imageUrl agar foto struk tampil di chat.
     */
    public function image(Request $request, string $uuid): BinaryFileResponse|JsonResponse
    {
        $user = $request->user();

        $evidence = Evidence::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->first();

        if (! $evidence) {
            return response()->json(['message' => 'Evidence tidak ditemukan.'], 404);
        }

        $disk = $evidence->disk ?? 'evidence';
        $path = $evidence->path;

        if (! Storage::disk($disk)->exists($path)) {
            return response()->json(['message' => 'File tidak ditemukan.'], 404);
        }

        $fullPath = Storage::disk($disk)->path($path);

        return response()->file($fullPath, [
            'Content-Type' => $evidence->mime_type ?? 'image/jpeg',
            'Content-Disposition' => 'inline; filename="'.$evidence->original_name.'"',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    /**
     * POST /chat/evidence/{uuid}/retry
     * Retry grouping LLM jika gagal (server LLM down) — chat turun seperti kirim lagi.
     * Cari bot message pending/failed untuk evidence ini, set jadi pending lagi & dispatch job.
     */
    public function retry(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        $evidence = Evidence::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->first();

        if (! $evidence) {
            return response()->json(['success' => false, 'message' => 'Evidence tidak ditemukan.'], 404);
        }

        // Cari conversation & bot message terakhir untuk evidence ini
        $conversation = Conversation::where('user_id', $user->id)
            ->where('is_active', true)
            ->latest()
            ->first();

        if (! $conversation) {
            return response()->json(['success' => false, 'message' => 'Conversation tidak ditemukan.'], 404);
        }

        $botMessage = ChatMessage::where('conversation_id', $conversation->id)
            ->where('role', 'assistant')
            ->where('status', '!=', 'completed')
            ->whereJsonContains('metadata->evidence_uuid', $uuid)
            ->latest('id')
            ->first();

        // Fallback: ambil bot terakhir dengan evidence_uuid apapun status
        if (! $botMessage) {
            $botMessage = ChatMessage::where('conversation_id', $conversation->id)
                ->where('role', 'assistant')
                ->whereJsonContains('metadata->evidence_uuid', $uuid)
                ->latest('id')
                ->first();
        }

        if (! $botMessage) {
            return response()->json(['success' => false, 'message' => 'Pesan bot untuk evidence ini tidak ditemukan.'], 404);
        }

        // Reset user bubble status jadi PROCESSING biar tidak stuck di FAILED
        $userMessage = ChatMessage::where('conversation_id', $conversation->id)
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

        if ($userMessage) {
            $content = $userMessage->content;
            $changed = false;
            foreach ($content as &$c) {
                if (($c['type'] ?? '') === 'image' && (($c['evidenceUuid'] ?? $c['evidence_uuid'] ?? '') === $uuid)) {
                    $c['evidenceStatus'] = 'PROCESSING';
                    $c['evidence_status'] = 'PROCESSING';
                    $changed = true;
                }
            }
            unset($c);
            if ($changed) {
                $userMessage->update(['content' => $content]);
            }
        }

        // Reset bot jadi pending & dispatch ulang
        $captionHint = $botMessage->metadata['caption_hint'] ?? '';
        $botMessage->update([
            'status' => 'pending',
            'content' => [],
            'error_message' => null,
            'metadata' => array_merge($botMessage->metadata ?? [], ['retry_at' => now()->toIso8601String()]),
        ]);

        EvidenceLlmGroupingJob::dispatch($evidence->id, $user->id, $botMessage->id, $captionHint);

        Log::info('Evidence retry dispatched', ['evidence_id' => $evidence->id, 'uuid' => $uuid, 'bot_message_id' => $botMessage->id]);

        return response()->json([
            'success' => true,
            'bot_message' => [
                'id' => $botMessage->id,
                'status' => 'pending',
            ],
        ]);
    }
}
