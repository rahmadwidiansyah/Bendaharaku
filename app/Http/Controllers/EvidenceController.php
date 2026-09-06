<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Chat\Adapters\WebAdapter;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EvidenceController extends Controller
{
    public function __construct(
        private readonly EvidenceUploadService $uploadService,
        private readonly EvidencePipelineService $pipelineService,
        private readonly WebAdapter $webAdapter,
    ) {}

    public function store(StoreEvidenceRequest $request): JsonResponse
    {
        return $this->handleUpload($request, Evidence::SOURCE_CHAT_UPLOAD, false);
    }

    /**
     * POST /chat/evidence/share — Web Share Target (PWA).
     * Dipanggil saat user share foto dari Galeri / app Bank (BCA, SeaBank, dll).
     * Membuat Evidence + ChatMessage (bubble) agar langsung muncul di /chat.
     */
    public function share(Request $request): JsonResponse|RedirectResponse
    {
        Log::info('ShareTarget hit', [
            'hasFile_image' => $request->hasFile('image'),
            'hasFile_file' => $request->hasFile('file'),
            'allFiles' => array_keys($request->allFiles()),
            'input' => $request->only(['title', 'text']),
            'headers' => $request->headers->all(),
        ]);

        // Share Target dari OS kadang kirim `image` (sesuai manifest), kadang `file` atau array.
        // Validasi longgar: terima file apapun dengan key image/file/files
        try {
            $request->validate([
                'image' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,heic,heif', 'dimensions:max_width=10000,max_height=10000'],
                'file' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,heic,heif'],
                'title' => ['nullable', 'string', 'max:500'],
                'text' => ['nullable', 'string', 'max:2000'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('ShareTarget validation failed', ['errors' => $e->errors(), 'allFiles' => $request->allFiles()]);

            return $request->expectsJson() || $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'File tidak valid.', 'errors' => $e->errors()], 422)
                : redirect()->route('chat.index')->with('error', 'File tidak valid: '.collect($e->errors())->flatten()->implode(', '));
        }

        if (! $request->hasFile('image') && ! $request->hasFile('file') && empty($request->allFiles())) {
            Log::warning('ShareTarget no file', ['all' => $request->all(), 'files' => $request->allFiles()]);

            return $request->expectsJson() || $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'File gambar wajib dipilih.'], 422)
                : redirect()->route('chat.index')->with('error', 'File gambar wajib dipilih. Coba share ulang dari Gallery.');
        }

        // Hold in composer: jangan prefill caption dari Gallery title/text, biar user ketik dari nol
        $caption = '';

        // Reuse handleUpload logic tapi hold (jangan auto enqueue)
        return $this->handleUpload($request, Evidence::SOURCE_SHARE, true, $caption);
    }

    private function handleUpload(Request $request, string $source, bool $isShare, string $captionHint = ''): JsonResponse|RedirectResponse
    {
        try {
            $file = $request->file('image') ?? $request->file('file');
            if (! $file) {
                $all = collect($request->allFiles())->flatten()->filter();
                $file = $all->first();
            }
            $user = $request->user();

            $result = $this->uploadService->upload(
                user: $user,
                file: $file,
                source: $source,
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

            // Share Target (PWA): hold di composer, jangan auto-send
            if ($isShare) {
                Log::info('ShareTarget hold', [
                    'evidence_id' => $evidence->id,
                    'uuid' => $evidence->uuid,
                    'user_id' => $user->id,
                ]);

                if ($request->expectsJson() || $request->wantsJson()) {
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
                }

                return redirect()->route('chat.index', ['share_evidence_uuid' => $evidence->uuid, 'share_hold' => 1]);
            }

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
