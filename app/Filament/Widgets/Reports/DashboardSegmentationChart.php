<?php

namespace App\Filament\Widgets\Reports;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;

class DashboardSegmentationChart extends ChartWidget
{
    protected ?string $heading = 'Donor Segmentation';
    
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $service = app(ReportService::class);
        $segmentation = $service->getDonorSegmentation('category');
        
        $colors = ['#4a6fa5', '#198754', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c'];
        
        return [
            'datasets' => [
                [
                    'data' => array_column($segmentation, 'donor_count'),
                    'backgroundColor' => array_slice($colors, 0, count($segmentation)),
                ],
            ],
            'labels' => array_column($segmentation, 'segment'),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
