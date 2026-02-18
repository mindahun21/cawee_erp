<?php

namespace App\Filament\Widgets\Reports;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class CampaignStatusDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Campaign Status Distribution';
    
    protected ?string $maxHeight = '250px';

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
        $campaigns = $service->getCampaignPerformanceData($this->filters);
        
        $statusCounts = [];
        foreach ($campaigns as $campaign) {
            $status = $campaign['status'] ?? 'unknown';
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }

        $colors = [
            'active' => '#198754',
            'completed' => '#4a6fa5',
            'draft' => '#6c757d',
            'scheduled' => '#0dcaf0',
            'archived' => '#212529',
            'unknown' => '#ffc107'
        ];

        $labels = array_keys($statusCounts);
        $data = array_values($statusCounts);
        $bgColors = array_map(fn($s) => $colors[$s] ?? $colors['unknown'], $labels);

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $bgColors,
                ],
            ],
            'labels' => array_map('ucfirst', $labels),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
