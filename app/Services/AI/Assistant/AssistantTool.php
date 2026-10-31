<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant;

use App\Models\User;

/**
 * Kontrak tool assistant.
 *
 * Tool SELALU read-only dan menerima User terautentikasi dari agent loop —
 * user_id tidak pernah diambil dari argumen model. Implementasi wajib
 * membatasi query pada user tersebut dan memakai rules() untuk validasi
 * parameter sebelum eksekusi.
 */
interface AssistantTool
{
    /** Nama unik tool yang dikirim ke provider. */
    public function name(): string;

    /** Deskripsi singkat untuk dipilih model. */
    public function description(): string;

    /**
     * Definisi provider-agnostic:
     * ['name' => ..., 'description' => ..., 'parameters' => JSON Schema].
     */
    public function schema(): array;

    /** Aturan validasi Laravel untuk parameter. */
    public function rules(): array;

    /**
     * Eksekusi tool untuk user yang sedang terautentikasi.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function execute(User $user, array $params): array;
}
