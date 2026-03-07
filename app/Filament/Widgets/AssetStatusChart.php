<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class AssetStatusChart extends ChartWidget
{
    protected ?string $heading = 'Asset Status Distribution';

    protected function getData(): array
    {
        $data = \App\Models\Asset::groupBy('status')
            ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->pluck('count', 'status')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Assets',
                    'data' => array_values($data),
                    'backgroundColor' => [
                        '#10b981', // green
                        '#3b82f6', // blue
                        '#f59e0b', // amber
                        '#ef4444', // red
                        '#6b7280', // gray
                    ],
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
