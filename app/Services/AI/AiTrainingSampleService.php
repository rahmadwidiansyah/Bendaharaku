<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiFeedbackLog;
use App\Models\AiTrainingSample;
use Illuminate\Support\Facades\DB;

class AiTrainingSampleService
{
    public function generateSampleFromFeedback(int $feedbackLogId): void
    {
        $feedback = AiFeedbackLog::find($feedbackLogId);
        if (!$feedback) {
            return;
        }

        $parseLog = DB::table('ai_parse_logs')->where('id', $feedback->parse_log_id)->first();
        if (!$parseLog) {
            return;
        }

        $signature = hash('sha256', $parseLog->user_id . '_' . trim(strtolower($parseLog->input_text)));

        AiTrainingSample::updateOrCreate(
            ['hash_signature' => $signature],
            [
                'source' => 'telegram_web_rlhf',
                'input_text' => $parseLog->input_text,
                'expected_json' => $feedback->corrected_payload,
                'quality_score' => 1.0000 - $feedback->divergence_score,
                'status' => 'raw',
                'is_verified' => true,
            ]
        );
    }
}