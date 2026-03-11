<?php

namespace App\Filament\Resources\HR\VehicleLicenses\Pages;

use App\Filament\Resources\HR\VehicleLicenses\VehicleLicenseResource;
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

