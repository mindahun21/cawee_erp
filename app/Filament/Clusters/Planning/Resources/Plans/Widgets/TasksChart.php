<?php

namespace App\Filament\Clusters\Planning\Resources\Plans\Widgets;

use App\Models\Task;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class TasksChart extends ChartWidget
{
    protected ?string $heading = 'Tasks Status distribution';

    protected function getData(): array
    {
        $data = [
            'pending' => Task::where('status', 'pending')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'completed' => Task::where('status', 'completed')->count(),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Tasks',
                    'data' => array_values($data),
                    'backgroundColor' => ['#9ca3af', '#fbbf24', '#22c55e'],
                ],
            ],
            'labels' => ['Pending', 'In Progress', 'Completed'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
