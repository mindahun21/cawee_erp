<?php

namespace App\Filament\Resources\Settings\VehicleServiceTypes\VehicleServiceTypes\Pages;

use App\Filament\Resources\Settings\VehicleServiceTypes\VehicleServiceTypes\VehicleServiceTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleServiceTypes extends ManageRecords
{
    protected static string $resource = VehicleServiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
