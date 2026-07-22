<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AIParseResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

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
            Log::error('Gagal membuat AI Parse Log: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Log ringkasan untuk multi-transaction batch.
     * Dipanggil setelah seluruh batch selesai diproses.
     *
     * @param  int  $successCount  Jumlah item berhasil
     * @param  int  $totalCount  Total item dalam batch
     * @param  array  $usage  ['prompt'=>int, 'completion'=>int, 'total'=>int]
     */
    public function createMultiLog(
        User $user,
        string $inputText,
        string $provider,
        string $model,
        float $confidence,
        int $successCount,
        int $totalCount,
        array $usage = [],
    ): int {
        try {
            $isSuccess = $successCount > 0;
            $status = $successCount === $totalCount ? 'parsed'
                       : ($successCount === 0 ? 'failed' : 'partial');

            return DB::table('ai_parse_logs')->insertGetId([
                'user_id' => $user->id,
                'transaction_log_id' => null,
                'provider' => $provider,
                'model' => $model,
                'input_text' => $inputText,
                'raw_response' => json_encode([
                    'type' => 'multi_transaction',
                    'total' => $totalCount,
                    'success_count' => $successCount,
                ]),
                'raw_confidence' => $confidence,
                'final_confidence' => $confidence,
                'is_success' => $isSuccess,
                'status' => $status,
                'error_message' => null,
                'prompt_tokens' => $usage['prompt'] ?? 0,
                'completion_tokens' => $usage['completion'] ?? 0,
                'total_tokens' => $usage['total'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Gagal membuat Multi AI Parse Log: '.$e->getMessage());

            return 0;
        }
    }
}
