<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Model keluarga antigravity terdaftar di Models API dengan generateContent,
     * tetapi endpoint generateContent menolaknya (hanya Interactions API).
     * Bersihkan katalog lama agar routing dan tes koneksi tidak memilihnya.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_model_catalog')) {
            return;
        }

        DB::table('ai_model_catalog')
            ->where('provider', 'gemini')
            ->whereRaw('LOWER(model_id) LIKE ?', ['antigravity%'])
            ->update([
                'is_eligible' => false,
                'supports_generate' => false,
                'last_error' => 'Hanya mendukung Interactions API (bukan generateContent).',
                'last_validated_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Tidak dikembalikan: model tersebut tetap tidak kompatibel dengan generateContent.
     */
    public function down(): void
    {
        // no-op
    }
};
