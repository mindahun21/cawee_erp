<?php

namespace App\Filament\Resources\VehicleManagement\VehicleAssignments\Pages;

use App\Filament\Resources\VehicleManagement\VehicleAssignments\VehicleAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVehicleAssignments extends ListRecords
{
    protected static string $resource = VehicleAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
