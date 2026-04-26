<?php

namespace App\Filament\Widgets\Reports;

use App\Traits\BelongsToModuleWidget;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class SegmentationChart extends ChartWidget
{
    use BelongsToModuleWidget;

    protected ?string $heading = 'Donor Segmentation';
    
    protected string $color = 'warning';
    
    public string $segmentBy = 'category';

    #[On('updateSegmentation')]
    public function updateSegmentation(string $segmentBy): void
    {
        $this->segmentBy = $segmentBy;
        $this->updateChartData();
    }

    protected function getData(): array
    {
        $service = app(ReportService::class);
        $data = $service->getDonorSegmentation($this->segmentBy);
        
        return [
            'datasets' => [
                [
                    'label' => 'Total Amount ($)',
                    'data' => array_column($data, 'total_amount'),
                    'backgroundColor' => [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'
                    ],
                ],
            ],
            'labels' => array_column($data, 'segment'),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
