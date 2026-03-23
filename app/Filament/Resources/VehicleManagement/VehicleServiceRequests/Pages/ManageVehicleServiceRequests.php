<?php

namespace App\Filament\Resources\VehicleManagement\VehicleServiceRequests\Pages;

use App\Filament\Resources\VehicleManagement\VehicleServiceRequests\VehicleServiceRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleServiceRequests extends ManageRecords
{
    protected static string $resource = VehicleServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
