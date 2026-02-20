<?php

namespace App\Filament\Widgets\Reports;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;

class MonthlyTrendChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Donation Trend';
    
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $service = app(ReportService::class);
        $trend = $service->getMonthlyDonationTrend();
        
        return [
            'datasets' => [
                [
                    'label' => 'Donation Amount ($)',
                    'data' => array_column($trend, 'total_amount'),
                    'borderColor' => '#4a6fa5',
                    'backgroundColor' => 'rgba(74, 111, 165, 0.1)',
                    'fill' => 'start',
                ],
                [
                    'label' => 'Donation Count',
                    'data' => array_column($trend, 'donation_count'),
                    'borderColor' => '#198754',
                    'backgroundColor' => 'rgba(25, 135, 84, 0.1)',
                    'fill' => 'start',
                ],
            ],
            'labels' => array_column($trend, 'month'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
