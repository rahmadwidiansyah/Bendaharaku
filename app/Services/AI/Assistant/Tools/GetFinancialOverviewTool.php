<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant\Tools;

use App\Models\User;
use App\Services\AI\Assistant\AssistantTool;
use App\Services\AI\Assistant\Tools\Concerns\SummarizesCashflow;
use App\Services\Loan\ActiveLoanCycleService;
use Illuminate\Support\Facades\DB;

final class GetFinancialOverviewTool implements AssistantTool
{
    use SummarizesCashflow;

    public function __construct(
        private readonly ActiveLoanCycleService $loanCycleService,
    ) {}

    public function name(): string
    {
        return 'get_financial_overview';
    }

    public function description(): string
    {
        return 'Ringkasan menyeluruh: total portofolio, aset likuid, investasi, hutang, piutang, dan arus kas bulan berjalan.';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
                'required' => [],
            ],
        ];
    }

    public function rules(): array
    {
        return [];
    }

    public function execute(User $user, array $params): array
    {
        $walletStats = DB::table('wallets')
            ->where('user_id', $user->id)
            ->whereIn('group_type', ['Asset', 'Liquid'])
            ->selectRaw(
                'COALESCE(SUM(balance), 0) as total_portfolio, '
                ."COALESCE(SUM(CASE WHEN group_type = 'Liquid' THEN balance ELSE 0 END), 0) as total_liquid, "
                ."COALESCE(SUM(CASE WHEN group_type = 'Asset' THEN balance ELSE 0 END), 0) as total_invest"
            )
            ->first();

        $loans = $this->loanCycleService->calculateForUser($user);
        $cashflow = $this->summarizeMonth($user, (int) now()->year, (int) now()->month);

        return [
            'portfolio' => [
                'total_portfolio' => (float) ($walletStats->total_portfolio ?? 0),
                'total_liquid' => (float) ($walletStats->total_liquid ?? 0),
                'total_invest' => (float) ($walletStats->total_invest ?? 0),
            ],
            'debts' => [
                'total_debt' => (float) ($loans['total_debt'] ?? 0),
                'total_receivable' => (float) ($loans['total_receivable'] ?? 0),
                'active_debt_count' => count($loans['debts'] ?? []),
                'active_receivable_count' => count($loans['receivables'] ?? []),
            ],
            'cashflow_this_month' => $cashflow,
        ];
    }
}
