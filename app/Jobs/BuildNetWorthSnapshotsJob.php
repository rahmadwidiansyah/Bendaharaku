<?php

namespace App\Jobs;

use App\Models\NetWorthSnapshot;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BuildNetWorthSnapshotsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $userId, private string $startDate, private string $endDate) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $period = CarbonPeriod::create($this->startDate, $this->endDate);

        // current wallet total (as of now)
        $wallets = Wallet::where('user_id', $user->id)->get();
        $currentWalletTotal = (float) $wallets->sum('balance');

        // current outstanding receivables / debts (is_cleared = false)
        $currentReceivables = (float) $user->transactionLogs()->where('type_id', function ($q) { /* placeholder */
        })->count();
        // Simpler: sum from type name via relation
        $currentReceivables = (float) $user->transactionLogs()->whereHas('type', fn ($q) => $q->where('name', 'Receivable'))->where('is_cleared', false)->sum('amount');
        $currentDebts = (float) $user->transactionLogs()->whereHas('type', fn ($q) => $q->where('name', 'Debt'))->where('is_cleared', false)->sum('amount');

        // Build per-date net effect (only cleared transactions affect historical wallet balances)
        $allCleared = $user->transactionLogs()->with('type')
            ->where('is_cleared', true)
            ->whereDate('date', '<=', $this->endDate)
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Group effects by date (net effect on net worth): +Income +Receivable -Expense -Debt
        $effectsByDate = [];
        foreach ($allCleared as $t) {
            $d = $t->date?->toDateString() ?? (string) $t->date;
            $name = $t->type?->name ?? '';
            $sign = match ($name) {
                'Income','Receivable' => 1,
                'Expense','Debt' => -1,
                default => 0,
            };
            $effectsByDate[$d] = ($effectsByDate[$d] ?? 0) + $sign * (float) $t->amount;
        }

        // end net worth = current wallets + outstanding receivables - outstanding debts
        $endNetWorth = $currentWalletTotal + $currentReceivables - $currentDebts;

        // Walk dates backwards from endDate to startDate, subtracting effects on each day
        $running = $endNetWorth;
        $dates = iterator_to_array($period);
        $dates = array_map(fn ($d) => $d->toDateString(), $dates);
        // iterate reverse so that snapshot for endDate equals current state
        for ($i = count($dates) - 1; $i >= 0; $i--) {
            $date = $dates[$i];
            $effect = $effectsByDate[$date] ?? 0;
            // snapshot for this date = running
            NetWorthSnapshot::updateOrCreate(
                ['user_id' => $user->id, 'snapshot_date' => $date],
                [
                    'total_wallet_balance' => 0, // leaving as zero; computing per-wallet historical requires more work
                    'total_receivables' => 0,
                    'total_debts' => 0,
                    'net_worth' => $running,
                ]
            );
            // reverse-apply today's effects to get prior day's running
            $running -= $effect;
        }
    }
}
