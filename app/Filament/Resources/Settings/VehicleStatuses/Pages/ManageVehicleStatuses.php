<?php

namespace App\Filament\Resources\Settings\VehicleStatuses\Pages;

use App\Filament\Resources\Settings\VehicleStatuses\VehicleStatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleStatuses extends ManageRecords
{
    protected static string $resource = VehicleStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
