<?php

declare(strict_types=1);

namespace App\Services\Evidence;

use App\Enums\EvidenceStatus;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * EvidenceUploadService — Mengelola upload file bukti (gambar) ke storage private.
 *
 * Tanggung jawab:
 * 1. Upload file ke storage/app/private/evidence/{user_id}/
 * 2. Buat record Evidence di database
 * 3. Generate URL untuk akses file
 *
 * TIDAK melakukan OCR, parsing, atau AI processing — itu untuk sprint berikutnya.
 */
class EvidenceUploadService
{
    /**
     * Upload gambar dan buat record Evidence.
     *
     * @return array{evidence: Evidence, url: string}
     *
     * @throws RuntimeException jika upload gagal
     */
    public function upload(User $user, UploadedFile $file, string $source = Evidence::SOURCE_CHAT_UPLOAD): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid().'.'.$extension;
        $size = $file->getSize();

        // Path: user_{id}/original/{filename}
        $path = $user->id.'/original/'.$storedName;

        // Upload ke storage
        $stored = Storage::disk('evidence')->put($path, $file->get());

        if (! $stored) {
            throw new RuntimeException('Gagal menyimpan file ke storage.');
        }

        // Buat record
        $evidence = Evidence::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'size' => $size,
            'disk' => 'evidence',
            'path' => $path,
            'status' => EvidenceStatus::Uploaded,
            'source' => $source,
        ]);

        $url = Storage::disk('evidence')->url($path);

        return [
            'evidence' => $evidence,
            'url' => $url,
        ];
    }

    /**
     * Generate URL untuk file evidence.
     */
    public function getUrl(Evidence $evidence): string
    {
        return Storage::disk($evidence->disk)->url($evidence->path);
    }

    /**
     * Hapus file dan record evidence.
     */
    public function delete(Evidence $evidence): bool
    {
        // Hapus file
        if (Storage::disk($evidence->disk)->exists($evidence->path)) {
            Storage::disk($evidence->disk)->delete($evidence->path);
        }

        return $evidence->delete();
    }
}
