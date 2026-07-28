<?php

namespace App\Jobs;

use App\Models\NetWorthSnapshot;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class BuildNetWorthSnapshotsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const TYPE_INCOME = 1;
    private const TYPE_EXPENSE = 2;
    private const TYPE_DEBT = 4;
    private const TYPE_RECEIVABLE = 5;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public int $userId,
        public string $startDate,
        public string $endDate,
    ) {}

    /**
     * Arah kas WALLET (masuk = +, keluar = -).
     * HARUS identik dengan signedCashCase() di AnalyticsController.
     * Kalau dua tempat ini pernah divergen lagi, itu yang bikin bug lama muncul balik.
     */
    private function walletCashCase(): string
    {
        return "
            CASE
                WHEN transaction_logs.type_id = 1 THEN transaction_logs.amount
                WHEN transaction_logs.type_id = 2 THEN -transaction_logs.amount
                WHEN categories.category_name = 'Dapat Hutangan' THEN transaction_logs.amount
                WHEN categories.category_name = 'Bayar Cicilan Hutang' THEN -transaction_logs.amount
                WHEN categories.category_name = 'Terima Bayar Piutang' THEN transaction_logs.amount
                WHEN categories.category_name = 'Ngasih Piutang' THEN -transaction_logs.amount
                ELSE 0
            END
        ";
    }

    /**
     * Perubahan saldo HUTANG outstanding (liability):
     * - Dapat Hutangan        -> hutang bertambah (+)
     * - Bayar Cicilan Hutang  -> hutang berkurang (-)
     */
    private function debtDeltaCase(): string
    {
        return "
            CASE
                WHEN categories.category_name = 'Dapat Hutangan' THEN transaction_logs.amount
                WHEN categories.category_name = 'Bayar Cicilan Hutang' THEN -transaction_logs.amount
                ELSE 0
            END
        ";
    }

    /**
     * Perubahan saldo PIUTANG outstanding (asset):
     * - Ngasih Piutang         -> piutang bertambah (+)
     * - Terima Bayar Piutang   -> piutang berkurang (-)
     */
    private function receivableDeltaCase(): string
    {
        return "
            CASE
                WHEN categories.category_name = 'Ngasih Piutang' THEN transaction_logs.amount
                WHEN categories.category_name = 'Terima Bayar Piutang' THEN -transaction_logs.amount
                ELSE 0
            END
        ";
    }

    public function handle(): void
    {
        if (! User::whereKey($this->userId)->exists()) {
            return;
        }

        $firstTxDate = DB::table('transaction_logs')
            ->where('user_id', $this->userId)
            ->where('is_cleared', true)
            ->whereIn('type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE, self::TYPE_DEBT, self::TYPE_RECEIVABLE])
            ->min('date');

        if (! $firstTxDate) {
            return; // belum ada transaksi sama sekali
        }

        // Mulai akumulasi dari transaksi PERTAMA user (bukan dari $startDate),
        // supaya saldo kumulatif di hari $startDate akurat — bukan mulai dari nol.
        $rangeStart = Carbon::parse($firstTxDate)->lt(Carbon::parse($this->startDate))
            ? Carbon::parse($firstTxDate)
            : Carbon::parse($this->startDate);

        $dailyDeltas = DB::table('transaction_logs')
            ->join('categories', 'transaction_logs.category_id', '=', 'categories.id')
            ->selectRaw(
                'transaction_logs.date as tx_date,
                 SUM('.$this->walletCashCase().') as wallet_delta,
                 SUM('.$this->debtDeltaCase().') as debt_delta,
                 SUM('.$this->receivableDeltaCase().') as receivable_delta'
            )
            ->where('transaction_logs.user_id', $this->userId)
            ->where('transaction_logs.is_cleared', true)
            ->where('transaction_logs.date', '<=', $this->endDate)
            ->whereIn('transaction_logs.type_id', [self::TYPE_INCOME, self::TYPE_EXPENSE, self::TYPE_DEBT, self::TYPE_RECEIVABLE])
            ->groupBy('transaction_logs.date')
            ->get()
            ->keyBy('tx_date');

        $walletBalance = 0.0;
        $debtBalance = 0.0;
        $receivableBalance = 0.0;
        $now = Carbon::now();
        $rows = [];

        foreach (CarbonPeriod::create($rangeStart, $this->endDate) as $dateObj) {
            $dateStr = $dateObj->format('Y-m-d');
            $delta = $dailyDeltas->get($dateStr);

            $walletBalance += (float) ($delta->wallet_delta ?? 0);
            $debtBalance += (float) ($delta->debt_delta ?? 0);
            $receivableBalance += (float) ($delta->receivable_delta ?? 0);

            // Simpan snapshot hanya untuk hari di rentang yang diminta.
            // rangeStart yang lebih awal dari startDate cuma dipakai buat akumulasi.
            if ($dateStr >= $this->startDate) {
                $rows[] = [
                    'user_id' => $this->userId,
                    'snapshot_date' => $dateStr,
                    'total_wallet_balance' => $walletBalance,
                    'total_receivables' => $receivableBalance,
                    'total_debts' => $debtBalance,
                    'net_worth' => $walletBalance + $receivableBalance - $debtBalance,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (empty($rows)) {
            return;
        }

        // Upsert per batch — otomatis MENIMPA snapshot lama (termasuk yang masih
        // pakai formula sign lama) dengan angka yang sudah dihitung ulang dengan benar.
        collect($rows)->chunk(500)->each(function ($chunk) {
            NetWorthSnapshot::upsert(
                $chunk->toArray(),
                ['user_id', 'snapshot_date'],
                ['total_wallet_balance', 'total_receivables', 'total_debts', 'net_worth', 'updated_at']
            );
        });
    }
}