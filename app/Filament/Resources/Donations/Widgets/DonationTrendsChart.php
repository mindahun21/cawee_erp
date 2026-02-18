<?php

namespace App\Filament\Resources\Donations\Widgets;

use App\Services\DonationService;
use Filament\Widgets\ChartWidget;

class DonationTrendsChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Donation Trends';
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $service = app(DonationService::class);
        $trends = $service->getMonthlyTrends();

        $labels = array_column($trends, 'month');
        $amounts = array_column($trends, 'total_amount');
        $counts = array_column($trends, 'count');

        return [
            'datasets' => [
                [
                    'label' => 'Total Amount ($)',
                    'data' => $amounts,
                    'borderColor' => '#10b981', // success
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Donation Count',
                    'data' => $counts,
                    'borderColor' => '#3b82f6', // primary
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}
