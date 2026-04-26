<?php

namespace App\Filament\Widgets\DonorManagement;

use App\Traits\BelongsToModuleWidget;

use App\Services\DonationService;
use Filament\Widgets\ChartWidget;

class DonationTypeDistributionChart extends ChartWidget
{
    use BelongsToModuleWidget;

    protected ?string $heading = 'Donations by Type';
    
    protected int | string | array $columnSpan = 'half';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $service = app(DonationService::class);
        $distribution = $service->getDonationTypeDistribution();

        return [
            'datasets' => [
                [
                    'label' => 'Donations',
                    'data' => array_column($distribution, 'total_amount'),
                    'backgroundColor' => [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1'
                    ],
                ],
            ],
            'labels' => array_column($distribution, 'type'),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
