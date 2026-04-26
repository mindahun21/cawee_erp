<?php

namespace App\Filament\Widgets\Recruitment;

use App\Traits\BelongsToModuleWidget;

use App\Models\Recruitment\RecruitmentSkillCategory;
use Filament\Widgets\ChartWidget;

class RecruitmentSkillsChart extends ChartWidget
{
    use BelongsToModuleWidget;

    protected ?string $heading = 'Plans by Status';

    protected function getData(): array
    {
        $data = \App\Models\Recruitment\RecruitmentPlan::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        $labels = $data->pluck('status')->map(fn($status) => \App\Models\Recruitment\RecruitmentPlan::statusLabel($status))->toArray();
        $counts = $data->pluck('count')->toArray();

        // Standard Filament colors mapping for status
        $colorMap = [
            \App\Models\Recruitment\RecruitmentPlan::STATUS_DRAFT     => '#9ca3af', // gray-400
            \App\Models\Recruitment\RecruitmentPlan::STATUS_SUBMITTED => '#f59e0b', // amber-500
            \App\Models\Recruitment\RecruitmentPlan::STATUS_APPROVED  => '#10b981', // emerald-500
            \App\Models\Recruitment\RecruitmentPlan::STATUS_REJECTED  => '#ef4444', // red-500
            \App\Models\Recruitment\RecruitmentPlan::STATUS_CLOSED    => '#3b82f6', // blue-500
        ];

        $bgColors = $data->pluck('status')->map(fn($status) => $colorMap[$status] ?? '#6b7280')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Plans',
                    'data' => $counts,
                    'backgroundColor' => $bgColors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

