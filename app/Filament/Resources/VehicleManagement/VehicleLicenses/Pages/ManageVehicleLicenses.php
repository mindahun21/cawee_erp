<?php

namespace App\Filament\Resources\VehicleManagement\VehicleLicenses\Pages;

use App\Filament\Resources\VehicleManagement\VehicleLicenses\VehicleLicenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleLicenses extends ManageRecords
{
    protected static string $resource = VehicleLicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
