<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class AssetStatusChart extends ChartWidget
{
    protected ?string $heading = 'Asset Status Distribution';

    protected ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $data = \App\Models\Asset::join('asset_statuses', 'assets.asset_status_id', '=', 'asset_statuses.id')
            ->groupBy('asset_statuses.name')
            ->select('asset_statuses.name', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->pluck('count', 'name')
            ->toArray();

        $colors = [
            'rgba(16, 185, 129, 0.85)', // green (Available)
            'rgba(59, 130, 246, 0.85)', // blue (Assigned)
            'rgba(245, 158, 11, 0.85)', // amber (Maintenance)
            'rgba(239, 68, 68, 0.85)',  // red (Disposed/Broken)
            'rgba(107, 114, 128, 0.85)', // gray (Other)
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Assets',
                    'data' => array_values($data),
                    'backgroundColor' => array_slice(array_merge($colors, $colors), 0, count($data)),
                    'borderWidth' => 2,
                    'borderColor' => '#fff',
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
