<?php

declare(strict_types=1);

namespace App\Services\AI\Assistant\Tools;

use App\Models\User;
use App\Services\AI\Assistant\AssistantTool;
use App\Services\AI\Assistant\Tools\Concerns\SummarizesCashflow;

final class GetMonthlySummaryTool implements AssistantTool
{
    use SummarizesCashflow;

    public function name(): string
    {
        return 'get_monthly_summary';
    }

    public function description(): string
    {
        return 'Ringkasan pemasukan, pengeluaran, dan selisih bersih untuk satu bulan (maksimal 12 bulan terakhir).';
    }

    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'year' => [
                        'type' => 'integer',
                        'description' => 'Tahun, misalnya 2026. Default tahun berjalan.',
                    ],
                    'month' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 12,
                        'description' => 'Bulan 1-12. Default bulan berjalan.',
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function rules(): array
    {
        return [
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function execute(User $user, array $params): array
    {
        $year = (int) ($params['year'] ?? now()->year);
        $month = (int) ($params['month'] ?? now()->month);

        return $this->summarizeMonth($user, $year, $month);
    }
}
