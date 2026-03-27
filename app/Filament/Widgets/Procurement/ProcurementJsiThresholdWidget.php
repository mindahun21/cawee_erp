<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\Requisition;
use App\Services\Procurement\JsiThresholds;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * JSI Procurement Threshold Overview Widget
 *
 * Shows live PR counts per JSI threshold tier as stat cards.
 * Uses StatsOverviewWidget (no custom $view needed — avoids static redeclaration).
 */
class ProcurementJsiThresholdWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    // Custom heading rendered via override
    protected ?string $heading = 'JSI Procurement Threshold Distribution';

    protected ?string $description = 'Live count of purchase requisitions per JSI authorization tier (ETB denominated)';

    protected function getStats(): array
    {
        $micro      = Requisition::where('estimated_total', '<', JsiThresholds::SIMPLIFIED_MIN)->count();
        $simplified = Requisition::whereBetween('estimated_total', [JsiThresholds::SIMPLIFIED_MIN, JsiThresholds::SIMPLIFIED_MAX])->count();
        $rfq        = Requisition::whereBetween('estimated_total', [JsiThresholds::RFQ_MIN, JsiThresholds::RFQ_MAX])->count();
        $open       = Requisition::where('estimated_total', '>=', JsiThresholds::OPEN_MIN)->count();

        return [
            Stat::make('① Micro Purchase', $micro)
                ->description('< ETB 77,000 · Single quote · Direct PO')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),

            Stat::make('② Simplified / Competitive', $simplified)
                ->description('ETB 77K–1.54M · Min 3 quotes · Price analysis')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('info'),

            Stat::make('③ RFQ / RFP Based', $rfq)
                ->description('ETB 1.54M–38.5M · Formal bids · Evaluation committee')
                ->descriptionIcon('heroicon-m-document-magnifying-glass')
                ->color('warning'),

            Stat::make('④ Open Competition', $open)
                ->description('> ETB 38.5M · Public tender · Full open competition')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('danger'),
        ];
    }
}
