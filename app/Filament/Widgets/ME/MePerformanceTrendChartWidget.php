<?php

namespace App\Filament\Widgets\ME;

use App\Traits\BelongsToModuleWidget;

use App\Filament\Widgets\ME\Concerns\InteractsWithMeFilters;
use App\Services\ME\DashboardService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class MePerformanceTrendChartWidget extends ChartWidget
{
    use BelongsToModuleWidget;

    use InteractsWithMeFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Monthly Progress Trend';

    protected ?string $description = 'Shows average progress percentage by reporting month.';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $trend = app(DashboardService::class)->performanceTrend($this->getMeFilters());

        return [
            'labels' => $trend['labels'],
            'datasets' => [
                [
                    'label' => 'Progress %',
                    'data' => $trend['data'],
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                ],
            ],
        ];
    }
}
