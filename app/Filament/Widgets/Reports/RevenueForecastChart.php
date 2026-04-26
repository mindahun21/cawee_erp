<?php

namespace App\Filament\Widgets\Reports;

use App\Traits\BelongsToModuleWidget;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;

class RevenueForecastChart extends ChartWidget
{
    use BelongsToModuleWidget;

    protected ?string $heading = 'Revenue Forecast (Next 12 Months)';
    
    protected string $color = 'info';

    protected function getData(): array
    {
        $service = app(ReportService::class);
        $forecast = $service->getRecurringDonationForecast();
        
        return [
            'datasets' => [
                [
                    'label' => 'Expected Revenue',
                    'data' => array_column($forecast, 'expected_amount'),
                ],
            ],
            'labels' => array_column($forecast, 'month'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
