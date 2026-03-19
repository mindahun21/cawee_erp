<?php

namespace App\Filament\Widgets\Recruitment;

use Filament\Widgets\ChartWidget;

class RecruitmentSkillsChart extends ChartWidget
{
    protected ?string $heading = 'Skills by Category';

    protected function getData(): array
    {
        $data = \App\Models\Recruitment\RecruitmentSkill::select('category', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('category')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Skills',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                ],
            ],
            'labels' => $data->map(fn ($item) => $item->category ?? 'Uncategorized')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
