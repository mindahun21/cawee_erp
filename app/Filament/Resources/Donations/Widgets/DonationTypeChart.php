<?php

namespace App\Filament\Resources\Donations\Widgets;

use App\Services\DonationService;
use Filament\Widgets\ChartWidget;

class DonationTypeChart extends ChartWidget
{
    protected ?string $heading = 'Donations by Type';
    
    protected int | string | array $columnSpan = 'half';

    protected function getData(): array
    {
        $service = app(DonationService::class);
        $distribution = $service->getDonationTypeDistribution();

        $labels = array_column($distribution, 'type');
        $counts = array_column($distribution, 'count');
        $amounts = array_column($distribution, 'total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Donations',
                    'data' => $counts,
                    'backgroundColor' => [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1'
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
