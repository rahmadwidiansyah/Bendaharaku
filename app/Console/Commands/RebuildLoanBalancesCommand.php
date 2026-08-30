<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Loan\LoanBalanceService;
use Illuminate\Console\Command;

class RebuildLoanBalancesCommand extends Command
{
    protected $signature = 'loans:rebuild-balances {user_id? : Batasi rebuild untuk user tertentu}';

    protected $description = 'Membangun ulang ringkasan saldo hutang dan piutang dari transaction_logs';

    public function handle(LoanBalanceService $service): int
    {
        $userId = $this->argument('user_id');
        $users = $userId ? User::whereKey($userId)->get() : User::query()->get();

        if ($users->isEmpty()) {
            $this->error('User tidak ditemukan.');

            return self::FAILURE;
        }

        foreach ($users as $user) {
            $service->rebuildAll($user->id);
            $this->info("Saldo hutang/piutang user {$user->id} berhasil dibangun ulang.");
        }

        return self::SUCCESS;
    }
}
