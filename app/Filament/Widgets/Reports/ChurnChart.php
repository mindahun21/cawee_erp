<?php

namespace App\Filament\Widgets\Reports;

use App\Traits\BelongsToModuleWidget;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;

class ChurnChart extends ChartWidget
{
    use BelongsToModuleWidget;

    protected ?string $heading = 'Donor Churn Rate (%)';
    
    protected string $color = 'danger';

    protected function getData(): array
    {
        $service = app(ReportService::class);
        $analysis = $service->getChurnAnalysis();
        
        return [
            'datasets' => [
                [
                    'label' => 'Churn Rate (%)',
                    'data' => array_column($analysis, 'churn_rate'),
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
