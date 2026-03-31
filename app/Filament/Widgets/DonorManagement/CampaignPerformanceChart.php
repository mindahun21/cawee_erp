<?php

namespace App\Filament\Widgets\DonorManagement;

use App\Models\Campaign;
use Filament\Widgets\ChartWidget;

class CampaignPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Active Campaign Progress (%)';
    
    protected int | string | array $columnSpan = 'half';

    protected function getData(): array
    {
        $activeCampaigns = Campaign::where('status', 'active')
            ->orderByDesc('total_raised')
            ->limit(5)
            ->get();

        $data = $activeCampaigns->map(function ($campaign) {
            $goal = $campaign->goal_amount > 0 ? $campaign->goal_amount : 1;
            return round(($campaign->total_raised / $goal) * 100, 2);
        });

        return [
            'datasets' => [
                [
                    'label' => 'Goal Percentage',
                    'data' => $data->toArray(),
                    'backgroundColor' => '#f59e0b', // warning
                ],
            ],
            'labels' => $activeCampaigns->pluck('title')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
