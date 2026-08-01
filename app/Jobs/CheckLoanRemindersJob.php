<?php

namespace App\Jobs;

use App\Models\LoanReminder;
use App\Models\TransactionLog;
use App\Models\User;
use App\Services\Loan\DueDateService;
use App\Services\Push\PushGate;
use App\Services\Push\PushPayloadBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CheckLoanRemindersJob — pengingat jatuh tempo hutang/piutang per user.
 *
 * Menemukan transaksi LOAN/RECEIVABLE aktif (saldo > 0) yang jatuh tempo
 * hari ini (due_date) atau besok (day_before), lalu kirim push — sekali
 * per (subject, tipe, reminder_type, due_date) via tabel loan_reminders.
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
        $tomorrow = $today->copy()->addDay();

        $transactions = $user->transactionLogs()
            ->with('category:id,category_name,system_key')
            ->where('is_cleared', true)
            ->whereNotNull('due_date_type')
            ->get();

        foreach ($transactions as $trx) {
            $this->processTransaction($user, $trx, $today, $tomorrow);
        }
    }

    private function processTransaction(User $user, TransactionLog $trx, Carbon $today, Carbon $tomorrow): void
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

        $balance = $this->balanceForSubject($user, $trx->subject, $systemKey);
        if ($balance <= 0) {
            return;
        }

        $nextDue = app(DueDateService::class)->nextDueDate($trx, $today);
        if ($nextDue === null) {
            return;
        }

        if ($nextDue->eq($today)) {
            $reminderType = 'due_date';
        } elseif ($nextDue->eq($tomorrow)) {
            $reminderType = 'day_before';
        } else {
            return;
        }

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

        PushGate::dispatchIfAway(
            $user,
            PushPayloadBuilder::loanReminder($user, $loanType, $reminderType, $trx->subject, $balance)
        );
    }

    private function balanceForSubject(User $user, string $subject, string $systemKey): float
    {
        $paidKey = $systemKey === 'LOAN' ? 'DEBT_PAYMENT' : 'RECEIVABLE_PAYMENT';

        $row = DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->where('transaction_logs.user_id', $user->id)
            ->where('transaction_logs.is_cleared', true)
            ->whereNull('transaction_logs.deleted_at')
            ->whereRaw('UPPER(TRIM(transaction_logs.subject)) = ?', [strtoupper(trim($subject))])
            ->selectRaw('
                SUM(CASE WHEN categories.system_key = ? THEN amount ELSE 0 END) as borrowed,
                SUM(CASE WHEN categories.system_key = ? THEN amount ELSE 0 END) as paid
            ', [$systemKey, $paidKey])
            ->first();

        return (float) ($row->borrowed ?? 0) - (float) ($row->paid ?? 0);
    }
}
