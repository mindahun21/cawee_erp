<?php

namespace App\Filament\Widgets\Recruitment;

use Filament\Widgets\ChartWidget;

class RecruitmentPlansChart extends ChartWidget
{
    protected ?string $heading = 'Vacancies by Department';

    protected function getData(): array
    {
        $data = \App\Models\Recruitment\RecruitmentPlan::query()
            ->join('hr_departments', 'hr_departments.id', '=', 'recruitment_plans.department_id')
            ->where('recruitment_plans.status', '!=', 'Closed')
            ->selectRaw('hr_departments.name as dept_name, sum(vacancies_needed) as total')
            ->groupBy('hr_departments.name')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Vacancies',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $data->pluck('dept_name')->toArray(),
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
