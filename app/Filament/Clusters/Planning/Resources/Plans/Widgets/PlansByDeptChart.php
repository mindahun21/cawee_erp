<?php

namespace App\Filament\Clusters\Planning\Resources\Plans\Widgets;

use App\Models\Plan;
use App\Models\Department;
use Filament\Widgets\ChartWidget;

class PlansByDeptChart extends ChartWidget
{
    protected ?string $heading = 'Strategic Plans by Department';

    protected function getData(): array
    {
        $data = Department::withCount('plans')->get();

        return [
            'datasets' => [
                [
                    'label' => 'Plans',
                    'data' => $data->pluck('plans_count')->toArray(),
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $data->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
