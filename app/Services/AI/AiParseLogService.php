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
            return DB::table('ai_parse_logs')->insertGetId(array_merge([
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
            ], $this->routingColumns($result->routing)));
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
     * @param  array|null  $routing  Metadata routing pool (fallback_from/to/reason, attempts, trace_id)
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
        ?array $routing = null,
    ): int {
        try {
            $isSuccess = $successCount > 0;
            $status = $successCount === $totalCount ? 'parsed'
                       : ($successCount === 0 ? 'failed' : 'partial');

            return DB::table('ai_parse_logs')->insertGetId(array_merge([
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
            ], $this->routingColumns($routing)));
        } catch (Throwable $e) {
            Log::error('Gagal membuat Multi AI Parse Log: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Kolom routing dari metadata executor (fallback/attempt ringkas, tanpa data sensitif).
     *
     * @return array<string, mixed>
     */
    private function routingColumns(?array $routing): array
    {
        $routing ??= [];
        $attempts = $routing['attempts'] ?? null;
        // Jangan simpan attempts mentah berlebihan ke DB; ringkas 8 entri pertama tanpa pesan sensitif.
        $routingAttempts = null;
        if (is_array($attempts)) {
            $routingAttempts = json_encode(array_map(fn ($a) => [
                'provider' => $a['provider'] ?? null,
                'account_id' => $a['account_id'] ?? null,
                'model' => $a['model'] ?? null,
                'category' => $a['category'] ?? null,
                'latency_ms' => $a['latency_ms'] ?? null,
            ], array_slice($attempts, 0, 8)));
        }

        return [
            'account_id' => ($routing['selected_account_id'] ?? 0) > 0 ? $routing['selected_account_id'] : null,
            'trace_id' => $routing['trace_id'] ?? null,
            'selection_method' => $routing['selection_method'] ?? null,
            'routing_attempts' => $routingAttempts,
            'fallback_from' => $routing['fallback_from'] ?? null,
            'fallback_to' => $routing['fallback_to'] ?? null,
            'fallback_reason' => $routing['fallback_reason'] ?? null,
        ];
    }
}
