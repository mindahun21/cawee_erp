<?php

namespace App\Filament\Resources\Settings\VehicleTypes\Pages;

use App\Filament\Resources\Settings\VehicleTypes\VehicleTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleTypes extends ManageRecords
{
    protected static string $resource = VehicleTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
