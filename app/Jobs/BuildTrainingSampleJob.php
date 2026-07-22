<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\AI\AiTrainingSampleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BuildTrainingSampleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 15;

    public function __construct(
        private readonly int $feedbackLogId
    ) {}

    public function handle(AiTrainingSampleService $trainingService): void
    {
        // Parameter dikirim dengan lengkap
        $trainingService->generateSampleFromFeedback($this->feedbackLogId);
    }
}
