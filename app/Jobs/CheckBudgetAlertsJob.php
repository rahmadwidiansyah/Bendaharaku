<?php

namespace App\Jobs;

use App\Models\BudgetGroup;
use App\Models\Category;
use App\Models\TransactionLog;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\Push\PushGate;
use App\Services\Push\PushPayloadBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * CheckBudgetAlertsJob — peringatan budget over (spent > target).
 *
 * Dipicu setelah transaksi Expense berhasil dibuat/confirm.
 * Push dikirim SEKALI per grup per bulan (kolom over_alert_sent_at) —
 * idempotent oleh desain.
 */
class CheckBudgetAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public int $userId,
        public int $month,
        public int $year,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $groups = BudgetGroup::with('expenseGroups')
            ->where('user_id', $user->id)
            ->where('period_month', $this->month)
            ->where('period_year', $this->year)
            ->whereNull('over_alert_sent_at')
            ->get();

        foreach ($groups as $group) {
            $spent = $this->spentForGroup($user, $group);

            if ($spent <= (float) $group->total_budget_amount) {
                continue;
            }

            $group->update(['over_alert_sent_at' => now()]);

            PushGate::dispatch($user, PushPayloadBuilder::budgetOver($user, $group));
        }
    }

    private function spentForGroup(User $user, BudgetGroup $group): float
    {
        $startDate = Carbon::create($group->period_year, $group->period_month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $expenseTypeId = TransactionType::where('name', 'Expense')->value('id');
        if ($expenseTypeId === null) {
            return 0.0;
        }

        $categoryIds = $group->items()
            ->where('budgetable_type', Category::class)
            ->pluck('budgetable_id');

        if ($categoryIds->isEmpty()) {
            return 0.0;
        }

        return (float) TransactionLog::where('user_id', $user->id)
            ->where('type_id', $expenseTypeId)
            ->where('is_cleared', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('category_id', $categoryIds)
            ->sum('amount');
    }
}
