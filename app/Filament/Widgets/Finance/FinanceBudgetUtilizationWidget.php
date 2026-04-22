<?php

namespace App\Filament\Widgets\Finance;

use App\Models\Finance\Budget;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class FinanceBudgetUtilizationWidget extends ChartWidget
{
    protected ?string $heading = 'Budget Utilization (Active Budgets)';
    protected static ?int $sort = 4;
    protected ?string $maxHeight = '320px';
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $data = Cache::remember('finance:budget_utilization', now()->addMinutes(10), function () {
            $budgets = Budget::whereIn('status', ['active', 'approved'])
                ->orderByDesc('total_budget_amount')
                ->limit(7)
                ->get();

            $labels     = [];
            $committed  = [];
            $encumbered = [];
            $actual     = [];
            $remaining  = [];

            foreach ($budgets as $b) {
                $labels[]     = $b->budget_code;
                $committed[]  = round((float)$b->committed_amount, 2);
                $encumbered[] = round((float)$b->encumbered_amount, 2);
                $actual[]     = round((float)$b->actual_spent, 2);
                $remaining[]  = round(max(0, $b->remaining()), 2);
            }

            return compact('labels', 'committed', 'encumbered', 'actual', 'remaining');
        });

        return [
            'datasets' => [
                [
                    'label'           => 'Committed',
                    'data'            => $data['committed'],
                    'backgroundColor' => 'rgba(245,158,11,0.7)',
                    'borderColor'     => 'rgba(245,158,11,1)',
                    'borderWidth'     => 1,
                ],
                [
                    'label'           => 'Encumbered',
                    'data'            => $data['encumbered'],
                    'backgroundColor' => 'rgba(249,115,22,0.7)',
                    'borderColor'     => 'rgba(249,115,22,1)',
                    'borderWidth'     => 1,
                ],
                [
                    'label'           => 'Actual Spent',
                    'data'            => $data['actual'],
                    'backgroundColor' => 'rgba(239,68,68,0.7)',
                    'borderColor'     => 'rgba(239,68,68,1)',
                    'borderWidth'     => 1,
                ],
                [
                    'label'           => 'Remaining',
                    'data'            => $data['remaining'],
                    'backgroundColor' => 'rgba(16,185,129,0.7)',
                    'borderColor'     => 'rgba(16,185,129,1)',
                    'borderWidth'     => 1,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
