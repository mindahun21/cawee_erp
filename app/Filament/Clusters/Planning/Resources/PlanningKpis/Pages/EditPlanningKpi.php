<?php

namespace App\Filament\Clusters\Planning\Resources\PlanningKpis\Pages;

use App\Filament\Clusters\Planning\Resources\PlanningKpis\PlanningKpiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlanningKpi extends EditRecord
{
    protected static string $resource = PlanningKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
