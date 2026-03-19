<?php

namespace App\Filament\Widgets\Recruitment;

use Filament\Widgets\ChartWidget;

class RecruitmentPlansChart extends ChartWidget
{
    protected ?string $heading = 'Active Plans by Department';

    protected function getData(): array
    {
        $data = \App\Models\Recruitment\RecruitmentPlan::with('department')
            ->where('status', '!=', 'closed')
            ->select('department_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('department_id')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Active Plans',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $data->map(fn ($item) => $item->department?->name ?? 'Unknown')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
