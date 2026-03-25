<?php

namespace App\Filament\Resources\VehicleManagement\Vehicles\Pages;

use App\Filament\Resources\VehicleManagement\Vehicles\VehicleFuelLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVehicleFuelLogs extends ListRecords
{
    protected static string $resource = VehicleFuelLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
