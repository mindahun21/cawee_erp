<?php

namespace App\Filament\Widgets\DonorManagement;

use App\Services\DonationService;
use Filament\Widgets\ChartWidget;

class DonationTrendsChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Donation Trends';
    
    protected int | string | array $columnSpan = 'half';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $service = app(DonationService::class);
        $trends = $service->getMonthlyTrends();

        return [
            'datasets' => [
                [
                    'label' => 'Total Amount (ETB)',
                    'data' => array_column($trends, 'total_amount'),
                    'borderColor' => '#10b981', // success
                ],
                [
                    'label' => 'Donation Count',
                    'data' => array_column($trends, 'count'),
                    'borderColor' => '#3b82f6', // primary
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
