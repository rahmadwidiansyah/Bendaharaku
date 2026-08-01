<?php

namespace App\Jobs;

use App\Services\Push\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public array $backoff = [10];

    public function __construct(
        public int $userId,
        public array $payload,
    ) {}

    public function handle(PushNotificationService $service): void
    {
        $service->sendToUser($this->userId, $this->payload);
    }
}
