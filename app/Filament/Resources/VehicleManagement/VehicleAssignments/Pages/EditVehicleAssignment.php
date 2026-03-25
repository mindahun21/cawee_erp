<?php

namespace App\Filament\Resources\VehicleManagement\VehicleAssignments\Pages;

use App\Filament\Resources\VehicleManagement\VehicleAssignments\VehicleAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditVehicleAssignment extends EditRecord
{
    protected static string $resource = VehicleAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
