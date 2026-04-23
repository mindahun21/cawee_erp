<?php

namespace App\Filament\Clusters\Planning\Resources\PlanningKpis\Pages;

use App\Filament\Clusters\Planning\Resources\PlanningKpis\PlanningKpiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlanningKpis extends ListRecords
{
    protected static string $resource = PlanningKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
