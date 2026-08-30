<?php

namespace App\Jobs;

use App\Models\LoanBalance;
use App\Models\LoanReminder;
use App\Models\TransactionLog;
use App\Models\User;
use App\Services\Loan\DueDateService;
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

        $transactions = $user->transactionLogs()
            ->with('category:id,category_name,system_key')
            ->where('is_cleared', true)
            ->whereNotNull('due_date_type')
            ->get();

        foreach ($transactions as $trx) {
            $this->processTransaction($user, $trx, $today);
        }
    }

    private function processTransaction(User $user, TransactionLog $trx, Carbon $today): void
    {
        if (! $trx->category || ! $trx->subject) {
            return;
        }

        $systemKey = (string) $trx->category->system_key;
        $loanType = match ($systemKey) {
            'LOAN' => 'debt',
            'RECEIVABLE' => 'receivable',
            default => null,
        };

        if ($loanType === null) {
            return;
        }

        $balance = (float) LoanBalance::where('user_id', $user->id)
            ->where('subject', strtoupper(trim($trx->subject)))
            ->where('loan_type', $loanType)
            ->value('balance');
        if ($balance <= 0) {
            return;
        }

        $nextDue = app(DueDateService::class)->nextDueDate($trx, $today);
        if ($nextDue === null) {
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
                'subject' => strtoupper(trim($trx->subject)),
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
            PushPayloadBuilder::loanReminder($user, $loanType, $reminderType, $trx->subject, $balance, $daysUntilDue)
        );
    }
}
