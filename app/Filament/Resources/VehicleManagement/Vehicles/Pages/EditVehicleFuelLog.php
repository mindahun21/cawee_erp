<?php

namespace App\Filament\Resources\VehicleManagement\Vehicles\Pages;

use App\Filament\Resources\VehicleManagement\Vehicles\VehicleFuelLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVehicleFuelLog extends EditRecord
{
    protected static string $resource = VehicleFuelLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
