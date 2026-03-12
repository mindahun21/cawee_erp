<?php

namespace App\Filament\Resources\HR\VehicleServiceRequests\Pages;

use App\Filament\Resources\HR\VehicleServiceRequests\VehicleServiceRequestResource;
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

