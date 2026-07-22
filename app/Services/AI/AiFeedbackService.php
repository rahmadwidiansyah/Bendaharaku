<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiFeedbackLog;
use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AiFeedbackService
{
    public function recordFeedback(User $user, TransactionLog $log, array $original, array $corrected): ?AiFeedbackLog
    {
        $parseLog = DB::table('ai_parse_logs')->where('transaction_log_id', $log->id)->first();
        if (! $parseLog) {
            return null;
        }

        // Weighted Divergence Score (Total 100% = 1.0000)
        $score = 0.0000;

        // Asumsi tipe transaksi direpresentasikan dari category/intent
        if (($original['transaction_type'] ?? '') !== ($corrected['transaction_type'] ?? '')) {
            $score += 0.4000;
        }
        if (($original['category_id'] ?? 0) !== ($corrected['category_id'] ?? 0)) {
            $score += 0.2500;
        }
        if (($original['source_wallet_id'] ?? 0) !== ($corrected['source_wallet_id'] ?? 0) ||
            ($original['destination_wallet_id'] ?? 0) !== ($corrected['destination_wallet_id'] ?? 0)) {
            $score += 0.2000;
        }
        if (($original['amount'] ?? 0) !== ($corrected['amount'] ?? 0)) {
            $score += 0.1000;
        }
        if (($original['subject'] ?? '') !== ($corrected['subject'] ?? '')) {
            $score += 0.0500;
        }

        return AiFeedbackLog::create([
            'parse_log_id' => $parseLog->id,
            'user_id' => $user->id,
            'original_payload' => $original,
            'corrected_payload' => $corrected,
            'divergence_score' => $score,
        ]);
    }
}
