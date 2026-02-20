<?php

namespace App\Filament\Widgets\Reports;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class CampaignComparisonChart extends ChartWidget
{
    protected ?string $heading = 'Campaign Performance Comparison';
    
    protected ?string $maxHeight = '300px';

    public string $metric = 'raised';
    public array $filters = [];

    #[On('updatePerformanceFilters')]
    public function updateFilters(array $filters): void
    {
        $this->filters = $filters;
        $this->getData();
    }

    protected function getData(): array
    {
        $service = app(ReportService::class);
        $campaigns = array_slice($service->getCampaignPerformanceData($this->filters), 0, 10);
        
        $labels = array_map(fn($c) => strlen($c['title']) > 20 ? substr($c['title'], 0, 20) . '...' : $c['title'], $campaigns);
        
        $data = match($this->metric) {
            'donors' => array_column($campaigns, 'donor_count'),
            'progress' => array_column($campaigns, 'progress_percentage'),
            default => array_column($campaigns, 'total_raised'),
        };

        $label = match($this->metric) {
            'donors' => 'Donor Count',
            'progress' => 'Goal Progress (%)',
            default => 'Amount Raised ($)',
        };

        $color = match($this->metric) {
            'donors' => 'rgba(25, 135, 84, 0.8)',
            'progress' => 'rgba(108, 117, 125, 0.8)',
            default => 'rgba(74, 111, 165, 0.8)',
        };

        return [
            'datasets' => [
                [
                    'label' => $label,
                    'data' => $data,
                    'backgroundColor' => $color,
                    'borderColor' => str_replace('0.8', '1', $color),
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
