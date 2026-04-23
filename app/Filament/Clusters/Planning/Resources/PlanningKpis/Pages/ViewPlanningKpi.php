<?php

namespace App\Filament\Clusters\Planning\Resources\PlanningKpis\Pages;

use App\Filament\Clusters\Planning\Resources\PlanningKpis\PlanningKpiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlanningKpi extends ViewRecord
{
    protected static string $resource = PlanningKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
