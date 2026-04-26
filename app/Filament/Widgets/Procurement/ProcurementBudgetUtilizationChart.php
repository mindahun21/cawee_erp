<?php

namespace App\Filament\Widgets\Procurement;

use App\Traits\BelongsToModuleWidget;

use App\Models\Procurement\ProcurementBudget;
use Filament\Widgets\ChartWidget;

class ProcurementBudgetUtilizationChart extends ChartWidget
{
    use BelongsToModuleWidget;

    protected ?string $heading = 'Budget Utilization by Line';
    protected static ?int $sort = 2;
    protected ?string $maxHeight = '320px';
    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $budgets   = ProcurementBudget::where('status', 'Active')->orderByDesc('allocated_amount')->limit(8)->get();
        $labels    = $budgets->pluck('code')->toArray();
        $allocated = $budgets->map(fn ($b) => (float) $b->allocated_amount)->toArray();
        $expended  = $budgets->map(fn ($b) => (float) $b->expended_amount)->toArray();
        $committed = $budgets->map(fn ($b) => (float) $b->committed_amount)->toArray();

        return [
            'datasets' => [
                ['label' => 'Allocated', 'data' => $allocated, 'backgroundColor' => 'rgba(59,130,246,0.2)', 'borderColor' => 'rgba(59,130,246,0.8)', 'borderWidth' => 2],
                ['label' => 'Committed', 'data' => $committed, 'backgroundColor' => 'rgba(245,158,11,0.6)', 'borderColor' => 'rgba(245,158,11,0.9)', 'borderWidth' => 2],
                ['label' => 'Expended',  'data' => $expended,  'backgroundColor' => 'rgba(16,185,129,0.6)', 'borderColor' => 'rgba(16,185,129,0.9)', 'borderWidth' => 2],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string { return 'bar'; }
}
