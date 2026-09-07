<?php

declare(strict_types=1);

namespace App\Chat\Components;

readonly class BarChartComponent implements ChatComponentInterface
{
    public function __construct(
        public string $title,
        public array $labels,
        public array $incomeData,
        public array $expenseData,
        public string $emoji = 'bar-chart-3',
        public ?string $translationKey = null,
        public float $totalIncome = 0,
        public float $totalExpense = 0,
    ) {}

    public function type(): string
    {
        return 'bar_chart';
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'title' => $this->title,
            'emoji' => $this->emoji,
            'labels' => $this->labels,
            'incomeData' => $this->incomeData,
            'expenseData' => $this->expenseData,
            'translationKey' => $this->translationKey,
            'totalIncome' => $this->totalIncome,
            'totalExpense' => $this->totalExpense,
        ];
    }
}
