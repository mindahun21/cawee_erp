<?php

namespace App\Filament\Widgets\ME;

use App\Filament\Widgets\ME\Concerns\InteractsWithMeFilters;
use App\Services\ME\DashboardService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class MeProgressByFrameworkChartWidget extends ChartWidget
{
    use InteractsWithMeFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Progress vs Target by Framework';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $dataset = app(DashboardService::class)->frameworkProgressChart($this->getMeFilters());

        return [
            'labels' => $dataset['labels'],
            'datasets' => [
                [
                    'label' => 'Target',
                    'data' => $dataset['target'],
                    'backgroundColor' => '#64748b',
                ],
                [
                    'label' => 'Actual',
                    'data' => $dataset['actual'],
                    'backgroundColor' => '#22c55e',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
