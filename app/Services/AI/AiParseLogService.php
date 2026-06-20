<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\User;
use App\DTO\AIParseResult;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Support\Facades\Log;

class AiParseLogService
{
    /**
     * Berjalan sinkronus. Mengembalikan ID untuk dibawa ke seluruh siklus transaksi.
     */
    public function createLog(User $user, string $inputText, AIParseResult $result, float $finalConfidence): int
    {
        try {
            return DB::table('ai_parse_logs')->insertGetId([
                'user_id' => $user->id,
                'transaction_log_id' => null, // Akan di-update oleh listener LinkParseLogToTransaction
                'provider' => $result->provider,
                'model' => $result->model,
                'input_text' => $inputText,
                'raw_response' => $result->transaction ? json_encode($result->transaction) : null,
                'raw_confidence' => $result->confidence, // Sesuai migration baru
                'final_confidence' => $finalConfidence,  // Dari parameter Orchestrator
                'is_success' => $result->success,
                'status' => 'parsed',
                'error_message' => $result->error,
                'prompt_tokens' => $result->usage['prompt'] ?? 0,
                'completion_tokens' => $result->usage['completion'] ?? 0,
                'total_tokens' => $result->usage['total'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Gagal membuat AI Parse Log: ' . $e->getMessage());
            return 0;
        }
    }
}