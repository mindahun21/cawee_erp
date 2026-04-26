<?php

namespace App\Filament\Widgets\Procurement;

use App\Traits\BelongsToModuleWidget;

use App\Models\Procurement\Tender;
use Filament\Widgets\ChartWidget;

class ProcurementTenderPipelineWidget extends ChartWidget
{
    use BelongsToModuleWidget;

    protected ?string $heading = 'Tender Pipeline';
    protected static ?int $sort = 4;
    protected ?string $maxHeight = '280px';
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $stages = [
            'Draft'      => Tender::where('status', 'Draft')->count(),
            'Published'  => Tender::where('status', 'Published')->count(),
            'Closed'     => Tender::where('status', 'Closed')->count(),
            'Evaluation' => Tender::where('status', 'Evaluation')->count(),
            'Awarded'    => Tender::where('status', 'Awarded')->count(),
            'Cancelled'  => Tender::where('status', 'Cancelled')->count(),
        ];
        $filtered = array_filter($stages, fn ($v) => $v > 0) ?: ['No Data' => 0];

        return [
            'datasets' => [[
                'data'            => array_values($filtered),
                'backgroundColor' => ['rgba(156,163,175,0.8)','rgba(14,165,233,0.8)','rgba(245,158,11,0.8)','rgba(139,92,246,0.8)','rgba(16,185,129,0.8)','rgba(239,68,68,0.8)'],
                'borderWidth'     => 2,
                'borderColor'     => '#fff',
            ]],
            'labels' => array_keys($filtered),
        ];
    }

    protected function getType(): string { return 'pie'; }
}
