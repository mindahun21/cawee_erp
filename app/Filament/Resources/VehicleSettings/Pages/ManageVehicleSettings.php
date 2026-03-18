<?php

namespace App\Filament\Resources\VehicleSettings\Pages;

use App\Filament\Resources\VehicleSettings\VehicleSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleSettings extends ManageRecords
{
    protected static string $resource = VehicleSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
