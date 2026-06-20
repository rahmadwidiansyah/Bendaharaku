<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TransactionCorrected;
use App\Jobs\UpdateUserMemoryJob;
use App\Jobs\BuildTrainingSampleJob;
use App\Services\AI\AiFeedbackService;

class LearnFromCorrection
{
    public function __construct(
        private readonly AiFeedbackService $feedbackService
    ) {}

    /**
     * Memproses koreksi pengguna dan mendelegasikannya secara asinkronus ke antrean background job.
     */
    public function handle(TransactionCorrected $event): void
    {
        // 1. Catat feedback secara sinkronus untuk menjaga integritas histori audit web dashboard
        $feedbackLog = $this->feedbackService->recordFeedback(
            $event->user,
            $event->transactionLog,
            $event->originalData,
            $event->correctedData
        );

        if (!$feedbackLog) {
            return;
        }

        // 2. Lempar ke antrean background queue (Asinkronus penuh - Non-blocking)
        UpdateUserMemoryJob::dispatch($event->user->id, $event->correctedData);
        BuildTrainingSampleJob::dispatch($feedbackLog->id);
    }
}