<?php

namespace App\Filament\Widgets\Reports;

use App\Traits\BelongsToModuleWidget;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;

class RetentionChart extends ChartWidget
{
    use BelongsToModuleWidget;

    protected ?string $heading = 'Donor Retention Rate (%)';
    
    protected string $color = 'success';

    protected function getData(): array
    {
        $service = app(ReportService::class);
        $analysis = $service->getDonorRetentionAnalysis();
        
        return [
            'datasets' => [
                [
                    'label' => 'Retention Rate (%)',
                    'data' => array_column($analysis, 'retention_rate'),
                ],
            ],
            'labels' => array_column($analysis, 'period'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
