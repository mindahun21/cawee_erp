<?php

namespace App\Filament\Resources\VehicleManagement\VehicleInspections\Pages;

use App\Filament\Resources\VehicleManagement\VehicleInspections\VehicleInspectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleInspections extends ManageRecords
{
    protected static string $resource = VehicleInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
