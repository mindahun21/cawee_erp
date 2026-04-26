<?php

namespace App\Filament\Widgets\Procurement;

use App\Traits\BelongsToModuleWidget;

use App\Models\Procurement\PurchaseOrder;
use Filament\Widgets\ChartWidget;

class ProcurementPoByStatusChart extends ChartWidget
{
    use BelongsToModuleWidget;

    protected ?string $heading = 'Purchase Orders by Status';
    protected static ?int $sort = 3;
    protected ?string $maxHeight = '320px';
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $statuses = [
            'Draft'              => PurchaseOrder::where('overall_status', 'Draft')->count(),
            'Pending Approval'   => PurchaseOrder::where('overall_status', 'Pending Approval')->count(),
            'Approved'           => PurchaseOrder::where('overall_status', 'Approved')->count(),
            'Sent to Supplier'   => PurchaseOrder::where('overall_status', 'Sent to Supplier')->count(),
            'Partially Received' => PurchaseOrder::where('overall_status', 'Partially Received')->count(),
            'Received'           => PurchaseOrder::where('overall_status', 'Received')->count(),
            'Closed'             => PurchaseOrder::where('overall_status', 'Closed')->count(),
            'Cancelled'          => PurchaseOrder::where('overall_status', 'Cancelled')->count(),
        ];
        $filtered = array_filter($statuses, fn ($v) => $v > 0) ?: ['No Data' => 0];

        return [
            'datasets' => [[
                'data'            => array_values($filtered),
                'backgroundColor' => ['rgba(156,163,175,0.8)','rgba(245,158,11,0.8)','rgba(59,130,246,0.8)','rgba(14,165,233,0.8)','rgba(139,92,246,0.8)','rgba(16,185,129,0.8)','rgba(6,182,212,0.8)','rgba(239,68,68,0.8)'],
                'borderWidth'     => 2,
                'borderColor'     => '#fff',
            ]],
            'labels' => array_keys($filtered),
        ];
    }

    protected function getType(): string { return 'doughnut'; }
}
