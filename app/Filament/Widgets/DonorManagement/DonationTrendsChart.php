<?php

namespace App\Filament\Widgets\DonorManagement;

use App\Services\DonationService;
use Filament\Widgets\ChartWidget;

class DonationTrendsChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Donation Trends';
    
    protected ?string $maxHeight = '275px';
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $service = app(DonationService::class);
        $trends = $service->getMonthlyTrends();

        return [
            'datasets' => [
                [
                    'label' => 'Total Amount (ETB)',
                    'data' => array_column($trends, 'total_amount'),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => 'start',
                    'tension' => 0.4,
                    'type' => 'line',
                ],
                [
                    'label' => 'Donation Count',
                    'data' => array_column($trends, 'count'),
                    'type' => 'bar',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => array_column($trends, 'month'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
