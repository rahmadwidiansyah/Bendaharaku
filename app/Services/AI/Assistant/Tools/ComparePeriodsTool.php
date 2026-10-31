<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant\Tools;

use App\Models\User;
use App\Services\AI\Assistant\AssistantTool;
use App\Services\AI\Assistant\Tools\Concerns\SummarizesCashflow;

final class ComparePeriodsTool implements AssistantTool
{
    use SummarizesCashflow;

    public function name(): string
    {
        return 'compare_periods';
    }

    public function description(): string
    {
        return 'Bandingkan pemasukan, pengeluaran, dan selisih bersih antara dua bulan (format YYYY-MM).';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'period_a' => [
                        'type' => 'string',
                        'description' => 'Periode pertama, format YYYY-MM.',
                    ],
                    'period_b' => [
                        'type' => 'string',
                        'description' => 'Periode kedua, format YYYY-MM.',
                    ],
                ],
                'required' => ['period_a', 'period_b'],
            ],
        ];
    }

    public function rules(): array
    {
        return [
            'period_a' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'period_b' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ];
    }

    public function execute(User $user, array $params): array
    {
        $a = $this->parsePeriod((string) $params['period_a']);
        $b = $this->parsePeriod((string) $params['period_b']);

        $summaryA = $this->summarizeMonth($user, $a['year'], $a['month']);
        $summaryB = $this->summarizeMonth($user, $b['year'], $b['month']);

        return [
            'period_a' => $summaryA,
            'period_b' => $summaryB,
            'difference' => [
                'income' => $summaryB['income'] - $summaryA['income'],
                'expense' => $summaryB['expense'] - $summaryA['expense'],
                'net' => $summaryB['net'] - $summaryA['net'],
            ],
        ];
    }
}
