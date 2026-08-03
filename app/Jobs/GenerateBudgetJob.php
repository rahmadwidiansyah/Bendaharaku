<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\BudgetGenerationStatus as StatusEnum;
use App\Models\BudgetGenerationStatus;
use App\Models\User;
use App\Services\Budgeting\AIBudgetService;
use App\Services\Push\PushGate;
use App\Services\Push\PushPayloadBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * GenerateBudgetJob — generate budget AI di background queue.
 *
 * Tahan banting: request HTTP hanya dispatch job (202), pekerjaan LLM
 * berjalan di worker sehingga tidak batal saat user pindah halaman/tutup tab.
 *
 * Retry aman karena AIBudgetService bersifat idempotent
 * (upsert + delete/recreate items & expenseGroups).
 */
class GenerateBudgetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public array $backoff = [5, 15];

    public function __construct(
        public int $userId,
        public int $month,
        public int $year,
    ) {}

    public function handle(AIBudgetService $service): void
    {
        $status = $this->status();
        $status->update([
            'status' => StatusEnum::Processing->value,
            'error_message' => null,
        ]);

        $user = User::findOrFail($this->userId);
        $service->generate($user, $this->month, $this->year);

        $status->update(['status' => StatusEnum::Completed->value]);

        PushGate::dispatch(
            $user,
            PushPayloadBuilder::budgetCreated($user, $this->month, $this->year)
        );
    }

    /**
     * Dipanggil saat job gagal permanen (setelah semua percobaan).
     */
    public function failed(?Throwable $e): void
    {
        $this->status()->update([
            'status' => StatusEnum::Failed->value,
            'error_message' => $e?->getMessage(),
        ]);

        $user = User::find($this->userId);
        if ($user) {
            PushGate::dispatch(
                $user,
                PushPayloadBuilder::budgetGenerationFailed($user, $this->month, $this->year)
            );
        }
    }

    private function status(): BudgetGenerationStatus
    {
        return BudgetGenerationStatus::firstOrCreate(
            ['user_id' => $this->userId, 'year' => $this->year, 'month' => $this->month],
            ['status' => StatusEnum::Pending->value],
        );
    }
}
