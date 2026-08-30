<?php

namespace App\Jobs;

use App\Models\LoanReminder;
use App\Models\User;
use App\Services\Loan\ActiveLoanCycleService;
use App\Services\Loan\LoanBalanceService;
use App\Services\Push\PushGate;
use App\Services\Push\PushPayloadBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * CheckLoanRemindersJob — pengingat jatuh tempo hutang/piutang per user.
 *
 * Mengikuti logika widget "Upcoming Debts" di dashboard: transaksi LOAN/
 * RECEIVABLE aktif (saldo > 0) yang jatuh tempo dalam 7 hari ke depan
 * (termasuk yang sudah overdue) berhak menerima push — sekali per
 * (subject, tipe, reminder_type, due_date) via tabel loan_reminders.
 */
class CheckLoanRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public int $userId,
        public string $date,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user || ! $user->push_notifications) {
            return;
        }

        $today = Carbon::parse($this->date)->startOfDay();
        app(LoanBalanceService::class)->rebuildAll($user->id);

        $cycles = app(ActiveLoanCycleService::class)->calculateForUser($user, $today);

        foreach (array_merge($cycles['debts'], $cycles['receivables']) as $cycle) {
            $this->processCycle($user, $cycle, $today);
        }
    }

    private function processCycle(User $user, array $cycle, Carbon $today): void
    {
        $loanType = $cycle['type'];
        $balance = $cycle['balance'];
        $nextDue = $cycle['due_date'];
        if ($balance <= 0 || $nextDue === null) {
            return;
        }

        // Konsisten dengan widget dashboard: push hanya jika jatuh tempo
        // dalam 7 hari ke depan ATAU sudah terlewat (overdue).
        $daysUntilDue = (int) $today->diffInDays($nextDue, false);
        if ($daysUntilDue > 7) {
            return;
        }

        $reminderType = match (true) {
            $daysUntilDue < 0 => 'overdue',
            $daysUntilDue === 0 => 'due_date',
            $daysUntilDue === 1 => 'day_before',
            default => 'upcoming',
        };

        $reminder = LoanReminder::firstOrCreate(
            [
                'user_id' => $user->id,
                'subject' => $cycle['subject'],
                'loan_type' => $loanType,
                'reminder_type' => $reminderType,
                'due_date' => $nextDue->toDateString(),
            ],
            ['sent_at' => now()]
        );

        if (! $reminder->wasRecentlyCreated) {
            return;
        }

        PushGate::dispatch(
            $user,
            PushPayloadBuilder::loanReminder($user, $loanType, $reminderType, $cycle['subject'], $balance, $daysUntilDue)
        );
    }
}
